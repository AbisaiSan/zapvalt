<?php

namespace App\Import;

use App\Import\Media\MediaSource;
use App\Models\Message;
use App\Support\TextNormalizer;

/**
 * Decide o tipo de cada mensagem pelo corpo, como na tabela da especificação.
 */
class MessageClassifier
{
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private const VIDEO_EXT = ['mp4', 'mov', '3gp', 'mkv', 'webm'];

    private const AUDIO_EXT = ['opus', 'ogg', 'm4a', 'mp3', 'aac', 'amr', 'wav'];

    private const OMITTED = ['<mídia oculta>', '<arquivo de mídia oculto>', '<media omitted>'];

    /** Mídia não exportada no iPhone (o texto vem marcado com U+200E). */
    private const OMITTED_IOS = [
        'imagem ocultada', 'imagem omitida', 'vídeo omitido', 'video omitido', 'áudio ocultado', 'áudio omitido',
        'figurinha omitida', 'documento omitido', 'gif omitido',
        'image omitted', 'video omitted', 'audio omitted', 'sticker omitted', 'document omitted', 'gif omitted',
    ];

    private const DELETED = [
        'essa mensagem foi apagada', 'esta mensagem foi apagada', 'você apagou esta mensagem', 'você apagou essa mensagem',
        'this message was deleted', 'you deleted this message',
    ];

    private const ATTACHED = [
        '~^(.+?\.[A-Za-z0-9]{1,6}) \((?:arquivo anexado|file attached)\)$~u',
        '~<(?:anexado|attached): (.+?)>$~u',
    ];

    public function classify(RawMessage $raw, MediaSource $media): ClassifiedMessage
    {
        $body = $raw->body;
        $trimmed = trim($body);
        $lower = rtrim(mb_strtolower($trimmed), '.');
        $sender = $raw->sender;

        if (in_array($lower, self::OMITTED, true) || ($raw->marked && in_array($lower, self::OMITTED_IOS, true))) {
            return $this->make($raw, Message::MISSING, $sender, null);
        }

        $lines = explode("\n", $body);
        $first = trim(array_shift($lines));

        foreach (self::ATTACHED as $pattern) {
            if (preg_match($pattern, $first, $m)) {
                $fileName = trim($m[1]);
                $fileKind = self::fileKind($fileName);
                $caption = trim(implode("\n", $lines));

                return new ClassifiedMessage(
                    line: $raw->line,
                    sentAt: $raw->sentAt,
                    kind: $media->has($fileName) ? $fileKind : Message::MISSING,
                    sender: $sender,
                    body: $fileName,
                    caption: $caption === '' ? null : $caption,
                    fileName: $fileName,
                    dedupeKind: $fileKind,
                    rawSender: $sender,
                );
            }
        }

        if (in_array($lower, self::DELETED, true)) {
            return $this->make($raw, Message::DELETED, $sender, null);
        }

        if ($sender === null || $raw->marked) {
            $normalized = TextNormalizer::normalize($trimmed);

            if (str_contains($normalized, 'criptografia') || str_contains($normalized, 'encrypted')) {
                return $this->make($raw, Message::SYSTEM, null, $trimmed, 'lock');
            }

            if (preg_match('~^(chamada de voz|chamada de video|missed voice call|missed video call|voice call|video call)~', $normalized)) {
                return $this->make($raw, Message::CALL, null, $trimmed);
            }

            return $this->make($raw, Message::SYSTEM, null, $trimmed);
        }

        return $this->make($raw, Message::TEXT, $sender, $body);
    }

    public static function fileKind(string $fileName): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $base = basename($fileName);

        if (str_starts_with(strtoupper($base), 'STK-') || ($ext === 'webp' && str_contains(strtoupper($base), 'STICKER'))) {
            return Message::STICKER;
        }

        return match (true) {
            in_array($ext, self::IMAGE_EXT, true) => Message::IMAGE,
            in_array($ext, self::VIDEO_EXT, true) => Message::VIDEO,
            in_array($ext, self::AUDIO_EXT, true) => Message::AUDIO,
            default => Message::DOC,
        };
    }

    private function make(RawMessage $raw, string $kind, ?string $sender, ?string $body, ?string $icon = null): ClassifiedMessage
    {
        return new ClassifiedMessage(
            line: $raw->line,
            sentAt: $raw->sentAt,
            kind: $kind,
            sender: $sender,
            body: $body,
            systemIcon: $icon,
            rawSender: $raw->sender,
        );
    }
}
