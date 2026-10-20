<?php

namespace App\Import;

use App\Models\Message;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula datas, contagens e última mensagem de uma conversa e de seus participantes.
 */
class ConversationStats
{
    /** Avisos de sistema e chamadas não contam como mensagens nem viram prévia. */
    public const NOT_COUNTED = [Message::SYSTEM, Message::CALL];

    public static function refresh(int $conversationId): void
    {
        $range = DB::table('messages')
            ->where('conversation_id', $conversationId)
            ->selectRaw('MIN(sent_at) AS first_at, MAX(sent_at) AS last_at')
            ->first();

        $count = DB::table('messages')
            ->where('conversation_id', $conversationId)
            ->whereNotIn('kind', self::NOT_COUNTED)
            ->count();

        $last = DB::table('messages')
            ->where('conversation_id', $conversationId)
            ->whereNotIn('kind', self::NOT_COUNTED)
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->value('id');

        DB::table('conversations')->where('id', $conversationId)->update([
            'first_message_at' => $range->first_at,
            'last_message_at' => $range->last_at,
            'message_count' => $count,
            'last_message_id' => $last,
            'updated_at' => now(),
        ]);

        DB::update(
            'UPDATE participants SET message_count = (
                SELECT COUNT(*) FROM messages WHERE messages.participant_id = participants.id
            ) WHERE conversation_id = ?',
            [$conversationId],
        );
    }
}
