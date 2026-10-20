<?php

namespace App\Api;

/**
 * Cursor opaco de paginação: base64 de "sent_at|id" (ou "last_message_at|id" na lista).
 */
class Cursor
{
    public static function encode(?string $at, int $id): string
    {
        return rtrim(strtr(base64_encode(($at ?? '').'|'.$id), '+/', '-_'), '=');
    }

    /**
     * @return array{0: string, 1: int}|null
     */
    public static function decode(?string $cursor): ?array
    {
        if ($cursor === null || $cursor === '') {
            return null;
        }

        $raw = base64_decode(strtr($cursor, '-_', '+/'), true);

        if ($raw === false || ! preg_match('~^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})?\|(\d+)$~', $raw, $m)) {
            return null;
        }

        return [$m[1], (int) $m[2]];
    }
}
