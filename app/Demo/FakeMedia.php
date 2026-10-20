<?php

namespace App\Demo;

use App\Import\Media\MediaStore;
use Symfony\Component\Process\Process;

/**
 * Arquivos de mídia de mentira para a demonstração (imagens coloridas, áudio silencioso, PDFs vazios).
 */
class FakeMedia
{
    private const RATIOS = ['4/3' => [1200, 900], '3/4' => [900, 1200], '16/9' => [1280, 720], '1/1' => [800, 800]];

    public static function image(int $hue, string $ratio, string $label): string
    {
        [$w, $h] = self::RATIOS[$ratio] ?? self::RATIOS['4/3'];
        $image = imagecreatetruecolor($w, $h);
        [$r1, $g1, $b1] = self::hsl($hue, 0.35, 0.78);
        [$r2, $g2, $b2] = self::hsl($hue, 0.35, 0.72);
        $light = imagecolorallocate($image, $r1, $g1, $b1);
        $dark = imagecolorallocate($image, $r2, $g2, $b2);
        imagefill($image, 0, 0, $light);

        // Faixas diagonais, como o marcador de imagem do protótipo.
        imagesetthickness($image, 34);
        for ($x = -$h; $x < $w + $h; $x += 68) {
            imageline($image, $x, 0, $x + $h, $h, $dark);
        }

        $box = imagecolorallocatealpha($image, 255, 255, 255, 30);
        $text = imagecolorallocate($image, 38, 52, 60);
        imagefilledrectangle($image, 16, 16, 24 + strlen($label) * 9, 44, $box);
        imagestring($image, 5, 22, 22, $label, $text);

        ob_start();
        imagejpeg($image, null, 85);

        return (string) ob_get_clean();
    }

    public static function sticker(int $hue): string
    {
        $image = imagecreatetruecolor(256, 256);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        [$r, $g, $b] = self::hsl($hue, 0.55, 0.65);
        imagefilledellipse($image, 128, 128, 220, 220, imagecolorallocate($image, $r, $g, $b));

        ob_start();
        imagewebp($image);

        return (string) ob_get_clean();
    }

    /** Áudio WAV silencioso com a duração pedida (8 kHz, mono, 8 bits). */
    public static function audio(int $seconds): string
    {
        $rate = 8000;
        $data = str_repeat(chr(128), $rate * $seconds);

        return 'RIFF'.pack('V', 36 + strlen($data)).'WAVE'
            .'fmt '.pack('VvvVVvv', 16, 1, 1, $rate, $rate, 1, 8)
            .'data'.pack('V', strlen($data)).$data;
    }

    /** Vídeo colorido com ffmpeg, quando existir; sem ele, um arquivo que não abre (mostra o estado de erro). */
    public static function video(int $hue, int $seconds, string $target): void
    {
        $ffmpeg = MediaStore::tool('ffmpeg');

        if ($ffmpeg !== null) {
            [$r, $g, $b] = self::hsl($hue, 0.35, 0.6);
            $color = sprintf('0x%02X%02X%02X', $r, $g, $b);
            $process = new Process([$ffmpeg, '-v', 'error', '-y', '-f', 'lavfi', '-i', "color=c={$color}:s=640x360:d={$seconds}",
                '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', $target]);
            $process->setTimeout(120)->run();

            if ($process->isSuccessful()) {
                return;
            }
        }

        file_put_contents($target, "vídeo de demonstração (instale o ffmpeg para gerar um vídeo de verdade)\n");
    }

    /** PDF simples com N páginas, completado até o tamanho indicado no protótipo. */
    public static function pdf(int $pages, string $title, int $size): string
    {
        $objects = [];
        $kids = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $next = 4;

        for ($i = 1; $i <= $pages; $i++) {
            $text = sprintf('(%s - pagina %d de %d) Tj', preg_replace('~[^A-Za-z0-9 _.-]~', '', $title), $i, $pages);
            $stream = "BT /F1 18 Tf 72 720 Td {$text} ET";
            $objects[$next] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
            $objects[$next + 1] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents '.$next.' 0 R /Resources << /Font << /F1 3 0 R >> >> >>';
            $kids[] = ($next + 1).' 0 R';
            $next += 2;
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$pages.' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $n => $body) {
            $offsets[$n] = strlen($pdf);
            $pdf .= "{$n} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return self::pad($pdf, $size);
    }

    /** Documento qualquer (planilha, zip, csv) só com conteúdo de preenchimento. */
    public static function document(string $fileName, int $size): string
    {
        return self::pad("Arquivo de demonstração do ZapVault: {$fileName}\n", $size);
    }

    /** "240 KB" ou "1,2 MB" em bytes. */
    public static function bytes(string $size): int
    {
        if (! preg_match('~^([\d,.]+)\s*(KB|MB)$~i', trim($size), $m)) {
            return 10_000;
        }

        return (int) round((float) str_replace(',', '.', $m[1]) * (strtoupper($m[2]) === 'MB' ? 1024 ** 2 : 1024));
    }

    private static function pad(string $content, int $size): string
    {
        $missing = $size - strlen($content);

        if ($missing <= 2) {
            return $content;
        }

        // Preenchimento aleatório em comentário: não comprime (como um arquivo real) e não quebra o PDF.
        $filler = substr(chunk_split(base64_encode(random_bytes((int) ceil($missing * 0.75))), 76, "\n%"), 0, $missing - 2);

        return $content.'%'.$filler."\n";
    }

    /** @return array{0: int, 1: int, 2: int} */
    private static function hsl(int $hue, float $s, float $l): array
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($hue / 60, 2) - 1));
        $m = $l - $c / 2;
        [$r, $g, $b] = match (intdiv($hue % 360, 60)) {
            0 => [$c, $x, 0], 1 => [$x, $c, 0], 2 => [0, $c, $x],
            3 => [0, $x, $c], 4 => [$x, 0, $c], default => [$c, 0, $x],
        };

        return [(int) round(($r + $m) * 255), (int) round(($g + $m) * 255), (int) round(($b + $m) * 255)];
    }
}
