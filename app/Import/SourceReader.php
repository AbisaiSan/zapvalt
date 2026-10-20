<?php

namespace App\Import;

use App\Import\Media\NoMedia;
use App\Import\Media\ZipMedia;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Abre um .txt ou .zip importado e encontra as conversas dentro dele.
 * Pacotes .zip dentro de .zip são abertos até o nível configurado.
 */
class SourceReader
{
    /** @var list<ZipArchive> */
    private array $zips = [];

    /** @var list<string> */
    private array $tempFiles = [];

    public function __construct(private readonly string $tempDir) {}

    /**
     * @return list<ChatSource>
     */
    public function read(string $path, string $originalName): array
    {
        $head = (string) file_get_contents($path, false, null, 0, 4);

        if (str_starts_with($head, 'PK')) {
            return $this->readZip($path, $originalName, 1);
        }

        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) === 'zip') {
            throw new ImportException('O arquivo .zip está corrompido ou não é um .zip de verdade.');
        }

        return [new ChatSource(
            label: $originalName,
            conversationName: self::conversationName($originalName) ?? self::baseName($originalName),
            open: fn () => fopen($path, 'rb'),
            size: (int) filesize($path),
            media: new NoMedia,
        )];
    }

    /** Fecha os pacotes abertos e apaga os arquivos temporários. */
    public function close(): void
    {
        foreach ($this->zips as $zip) {
            @$zip->close();
        }

        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        $this->zips = [];
        $this->tempFiles = [];
    }

    /**
     * Nome da conversa a partir do nome do arquivo exportado, ou nulo se não casar com nenhum padrão.
     */
    public static function conversationName(string $fileName): ?string
    {
        $base = self::baseName($fileName);

        foreach ([
            '~^Conversa do WhatsApp com (.+)$~iu',
            '~^Conversa com (.+)$~iu',
            '~^WhatsApp Chat with (.+)$~iu',
            '~^WhatsApp Chat - (.+)$~iu',
        ] as $pattern) {
            if (preg_match($pattern, $base, $m)) {
                return trim($m[1]);
            }
        }

        return null;
    }

    private static function baseName(string $fileName): string
    {
        $base = basename(str_replace('\\', '/', $fileName));

        return trim((string) preg_replace('~\.(txt|zip)$~i', '', $base));
    }

    /**
     * @return list<ChatSource>
     */
    private function readZip(string $path, string $label, int $depth): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new ImportException('O arquivo .zip está corrompido ou não pôde ser aberto.');
        }

        $this->zips[] = $zip;
        $this->checkSafety($zip);

        $texts = [];
        $nested = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $base = basename($name);

            if (str_ends_with($name, '/') || str_starts_with($name, '__MACOSX/') || str_starts_with($base, '._')) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($ext === 'txt') {
                $texts[] = $name;
            } elseif ($ext === 'zip') {
                $nested[] = $name;
            }
        }

        $sources = [];

        foreach ($texts as $entry) {
            $stat = $zip->statName($entry);
            $sources[] = new ChatSource(
                label: count($texts) > 1 ? $label.' › '.basename($entry) : $label,
                conversationName: self::conversationName($entry)
                    ?? self::conversationName($label)
                    ?? (in_array(self::baseName($entry), ['_chat', 'chat'], true) ? self::baseName($label) : self::baseName($entry)),
                open: function () use ($zip, $entry) {
                    $stream = $zip->getStream($entry);
                    if ($stream === false) {
                        throw new ImportException('Não foi possível ler o texto da conversa dentro do .zip.');
                    }

                    return $stream;
                },
                size: (int) ($stat['size'] ?? 0),
                media: new ZipMedia($zip, $entry),
            );
        }

        if ($depth < (int) config('zapvault.import.max_zip_depth', 2)) {
            foreach ($nested as $entry) {
                $temp = $this->extractTemp($zip, $entry);
                array_push($sources, ...$this->readZip($temp, basename($entry), $depth + 1));
            }
        }

        return $sources;
    }

    /**
     * Recusa caminhos perigosos, pacotes grandes demais e razões de compressão suspeitas.
     */
    private function checkSafety(ZipArchive $zip): void
    {
        $maxEntries = (int) config('zapvault.import.max_entries');
        $maxBytes = (int) config('zapvault.import.max_uncompressed_bytes');
        $maxRatio = (int) config('zapvault.import.max_compression_ratio');
        $ratioMin = (int) config('zapvault.import.ratio_min_bytes');

        if ($zip->numFiles > $maxEntries) {
            throw new ImportException("O .zip tem arquivos demais ({$zip->numFiles}). O limite é {$maxEntries}.");
        }

        $total = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = (string) ($stat['name'] ?? '');
            $normalized = str_replace('\\', '/', $name);

            if (str_starts_with($normalized, '/') || preg_match('~^[A-Za-z]:~', $normalized)
                || in_array('..', explode('/', $normalized), true) || str_contains($name, "\0")) {
                throw new ImportException('O .zip contém um caminho de arquivo inválido e foi recusado por segurança.');
            }

            $size = (int) ($stat['size'] ?? 0);
            $compressed = max(1, (int) ($stat['comp_size'] ?? 0));
            $total += $size;

            if ($size >= $ratioMin && $size / $compressed > $maxRatio) {
                throw new ImportException('O .zip tem uma taxa de compressão suspeita e foi recusado por segurança.');
            }
        }

        if ($total > $maxBytes) {
            throw new ImportException('O .zip é grande demais depois de descompactado e foi recusado.');
        }
    }

    private function extractTemp(ZipArchive $zip, string $entry): string
    {
        $stream = $zip->getStream($entry);

        if ($stream === false) {
            throw new ImportException('Não foi possível abrir um .zip dentro do pacote.');
        }

        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0700, true);
        }

        $temp = $this->tempDir.'/'.Str::uuid().'.zip';
        $out = fopen($temp, 'wb');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);

        $this->tempFiles[] = $temp;

        return $temp;
    }
}
