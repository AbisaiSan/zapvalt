<?php

namespace App\Import\Media;

use App\Models\Attachment;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Guarda cada arquivo de mídia uma só vez, nomeado pelo SHA-256: {DATA}/media/ab/cd/{sha256}.{ext}.
 */
class MediaStore
{
    private const MIME = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
        'mp4' => 'video/mp4', 'mov' => 'video/quicktime', '3gp' => 'video/3gpp', 'mkv' => 'video/x-matroska', 'webm' => 'video/webm',
        'opus' => 'audio/ogg', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4', 'mp3' => 'audio/mpeg', 'aac' => 'audio/aac',
        'amr' => 'audio/amr', 'wav' => 'audio/wav', 'pdf' => 'application/pdf',
    ];

    /** @var array<string, string|null> */
    private static array $tools = [];

    public function __construct(private readonly string $root) {}

    public static function mime(string $ext): string
    {
        return self::MIME[strtolower($ext)] ?? 'application/octet-stream';
    }

    /**
     * Copia o arquivo em fluxo calculando o hash; se já existir no acervo, não regrava.
     *
     * @param  resource  $stream
     * @return array{sha256: string, path: string, size: int, ext: string}
     */
    public function store($stream, string $fileName): array
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $ext = preg_match('~^[a-z0-9]{1,8}$~', $ext) ? $ext : 'bin';

        $temp = $this->root.'/uploads-tmp/'.Str::uuid().'.part';
        $out = fopen($temp, 'wb');
        $hash = hash_init('sha256');
        $size = 0;

        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 1024 * 1024);
                if ($chunk === false) {
                    throw new RuntimeException('Falha ao ler a mídia do pacote.');
                }
                hash_update($hash, $chunk);
                fwrite($out, $chunk);
                $size += strlen($chunk);
            }
        } finally {
            fclose($out);
            fclose($stream);
        }

        $sha = hash_final($hash);
        $path = self::shardedPath('media', $sha, $ext);
        $absolute = $this->root.'/'.$path;

        if (file_exists($absolute)) {
            unlink($temp);
        } else {
            @mkdir(dirname($absolute), 0700, true);
            rename($temp, $absolute);
            @chmod($absolute, 0600);
        }

        return ['sha256' => $sha, 'path' => $path, 'size' => $size, 'ext' => $ext];
    }

    /**
     * Dimensões, miniatura e, se as ferramentas existirem, duração e páginas.
     *
     * @return array{width: ?int, height: ?int, thumb_path: ?string, duration_seconds: ?int, pages: ?int}
     */
    public function describe(string $kind, string $path, string $sha, string $ext): array
    {
        $absolute = $this->root.'/'.$path;
        $info = ['width' => null, 'height' => null, 'thumb_path' => null, 'duration_seconds' => null, 'pages' => null];

        if ($kind === Message::IMAGE || $kind === Message::STICKER) {
            $size = @getimagesize($absolute);
            if ($size !== false) {
                [$info['width'], $info['height']] = [$size[0], $size[1]];
            }
            if ($kind === Message::IMAGE) {
                $info['thumb_path'] = $this->thumbnail($absolute, $sha);
            }
        }

        if ($kind === Message::VIDEO) {
            $info = array_merge($info, $this->probeVideo($absolute, $sha));
        }

        if ($kind === Message::AUDIO) {
            $info['duration_seconds'] = $this->duration($absolute);
        }

        if ($ext === 'pdf') {
            $info['pages'] = $this->pdfPages($absolute);
        }

        return $info;
    }

    /**
     * Apaga do disco os arquivos que nenhum anexo usa mais.
     *
     * @param  iterable<string>  $hashes
     */
    public function deleteUnused(iterable $hashes): int
    {
        $deleted = 0;

        foreach (array_unique([...$hashes]) as $sha) {
            if (Attachment::query()->where('sha256', $sha)->exists()) {
                continue;
            }

            foreach (glob($this->root.'/'.self::shardedPath('media', $sha, '*')) ?: [] as $file) {
                $deleted += (int) @unlink($file);
            }
            @unlink($this->root.'/'.self::shardedPath('thumbs', $sha, 'jpg'));
        }

        return $deleted;
    }

    /**
     * Hashes dos anexos das mensagens selecionadas pela consulta.
     *
     * @return list<string>
     */
    public static function hashesForMessages(\Closure $where): array
    {
        return DB::table('attachments')
            ->join('messages', 'messages.id', '=', 'attachments.message_id')
            ->where($where)
            ->distinct()
            ->pluck('attachments.sha256')
            ->all();
    }

    public static function shardedPath(string $dir, string $sha, string $ext): string
    {
        return sprintf('%s/%s/%s/%s.%s', $dir, substr($sha, 0, 2), substr($sha, 2, 2), $sha, $ext);
    }

    public static function tool(string $name): ?string
    {
        return self::$tools[$name] ??= (new ExecutableFinder)->find($name, null, ['/opt/homebrew/bin', '/usr/local/bin']);
    }

    private function thumbnail(string $absolute, string $sha): ?string
    {
        $path = self::shardedPath('thumbs', $sha, 'jpg');
        $target = $this->root.'/'.$path;

        if (file_exists($target)) {
            return $path;
        }

        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($absolute));
        if ($image === false) {
            return null;
        }

        $max = (int) config('zapvault.import.thumb_size', 640);
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $max / max($width, $height));
        $w = max(1, (int) round($width * $scale));
        $h = max(1, (int) round($height * $scale));

        $thumb = imagecreatetruecolor($w, $h);
        imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $w, $h, $width, $height);

        @mkdir(dirname($target), 0700, true);
        $ok = imagejpeg($thumb, $target, 82);

        return $ok ? $path : null;
    }

    /**
     * @return array{width?: int, height?: int, duration_seconds?: ?int, thumb_path?: string}
     */
    private function probeVideo(string $absolute, string $sha): array
    {
        $info = [];
        $ffprobe = self::tool('ffprobe');

        if ($ffprobe !== null) {
            $process = new Process([$ffprobe, '-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height:format=duration', '-of', 'json', $absolute]);
            $process->setTimeout(30)->run();
            $data = json_decode($process->getOutput(), true) ?: [];
            $stream = $data['streams'][0] ?? [];
            if (isset($stream['width'], $stream['height'])) {
                $info['width'] = (int) $stream['width'];
                $info['height'] = (int) $stream['height'];
            }
            if (isset($data['format']['duration'])) {
                $info['duration_seconds'] = (int) round((float) $data['format']['duration']);
            }
        }

        $ffmpeg = self::tool('ffmpeg');

        if ($ffmpeg !== null) {
            $path = self::shardedPath('thumbs', $sha, 'jpg');
            $target = $this->root.'/'.$path;
            @mkdir(dirname($target), 0700, true);
            $process = new Process([$ffmpeg, '-v', 'error', '-y', '-ss', '0', '-i', $absolute, '-frames:v', '1', '-vf', 'scale=640:640:force_original_aspect_ratio=decrease', $target]);
            $process->setTimeout(60)->run();
            if ($process->isSuccessful() && file_exists($target)) {
                $info['thumb_path'] = $path;
            }
        }

        return $info;
    }

    private function duration(string $absolute): ?int
    {
        $ffprobe = self::tool('ffprobe');

        if ($ffprobe === null) {
            return null;
        }

        $process = new Process([$ffprobe, '-v', 'error', '-show_entries', 'format=duration', '-of', 'default=noprint_wrappers=1:nokey=1', $absolute]);
        $process->setTimeout(30)->run();
        $value = trim($process->getOutput());

        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    private function pdfPages(string $absolute): ?int
    {
        $pdfinfo = self::tool('pdfinfo');

        if ($pdfinfo === null) {
            return null;
        }

        $process = new Process([$pdfinfo, $absolute]);
        $process->setTimeout(30)->run();

        return preg_match('~^Pages:\s+(\d+)~m', $process->getOutput(), $m) ? (int) $m[1] : null;
    }
}
