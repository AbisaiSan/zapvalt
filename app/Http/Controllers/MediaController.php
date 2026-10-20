<?php

namespace App\Http\Controllers;

use App\Import\Media\MediaStore;
use App\Models\Attachment;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entrega os anexos em fluxo, com Range. O caminho vem sempre do banco e precisa ficar dentro da pasta de dados.
 */
class MediaController extends Controller
{
    /** Tipos que podem abrir no navegador; todo o resto é sempre baixado. */
    private const INLINE = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'mp4', 'mov', '3gp', 'mkv', 'webm',
        'opus', 'ogg', 'm4a', 'mp3', 'aac', 'amr', 'wav',
        'pdf',
    ];

    public function show(Request $request, Attachment $attachment): Response
    {
        return $this->send($request, $attachment, $attachment->storage_path, strtolower($attachment->ext), $attachment->sha256);
    }

    public function thumb(Request $request, Attachment $attachment): Response
    {
        if ($attachment->thumb_path !== null) {
            return $this->send($request, $attachment, $attachment->thumb_path, 'jpg', $attachment->sha256.'-thumb', inlineOnly: true);
        }

        // Sem miniatura (figurinhas, imagens pequenas): a própria imagem.
        abort_unless(in_array(strtolower($attachment->ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true), 404);

        return $this->send($request, $attachment, $attachment->storage_path, strtolower($attachment->ext), $attachment->sha256, inlineOnly: true);
    }

    private function send(Request $request, Attachment $attachment, string $relative, string $ext, string $etag, bool $inlineOnly = false): Response
    {
        $conversation = $attachment->message?->conversation;
        abort_if($conversation === null || $conversation->status !== Conversation::ACTIVE, 404);

        $root = realpath(config('zapvault.data_path'));
        $path = realpath($root.'/'.$relative);

        abort_if($root === false || $path === false || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR) || ! is_file($path), 404);

        $inline = in_array($ext, self::INLINE, true) && ! ($request->boolean('download') && ! $inlineOnly);

        $response = new BinaryFileResponse($path, 200, [
            'Content-Type' => $inline ? MediaStore::mime($ext) : 'application/octet-stream',
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ], public: false, autoEtag: false, autoLastModified: false);

        $response->setEtag($etag);
        $response->setContentDisposition(
            $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            $attachment->original_name,
            $this->asciiFallback($attachment->original_name),
        );

        // setEtag/Cache-Control podem ser reescritos; garante o cabeçalho da especificação.
        $response->headers->set('Cache-Control', 'private, max-age=31536000, immutable');

        // Com If-None-Match igual ao ETag, vira 304 sem corpo.
        $response->isNotModified($request);

        if ($this->unsatisfiableRange($request, (int) filesize($path))) {
            return response('', 416, ['Content-Range' => 'bytes */'.filesize($path)]);
        }

        return $response;
    }

    /** Intervalo que começa no fim do arquivo ou depois dele (o Symfony devolveria o arquivo inteiro). */
    private function unsatisfiableRange(Request $request, int $size): bool
    {
        $range = (string) $request->headers->get('Range');

        if (! $request->isMethod('GET') || ! preg_match('~^bytes=(\d+)-(\d*)$~', trim($range), $m)) {
            return false;
        }

        return (int) $m[1] >= $size || ($m[2] !== '' && (int) $m[2] < (int) $m[1]);
    }

    private function asciiFallback(string $name): string
    {
        $ascii = preg_replace('~[^\x20-\x7E]~', '_', $name) ?? 'arquivo';

        return str_replace(['/', '\\', '%', '"'], '_', $ascii);
    }
}
