<?php

namespace App\Api;

use App\Import\ConversationStats;
use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Formatos JSON da especificação (seção "Endpoints e contratos").
 */
class Presenter
{
    /** Data e hora como no arquivo, sem fuso: 2026-10-20T09:21:00. */
    public static function time(?string $value): ?string
    {
        return $value === null ? null : str_replace(' ', 'T', substr($value, 0, 19));
    }

    /**
     * Itens da lista de conversas, com a prévia montada no servidor.
     *
     * @param  Collection<int, object>  $rows  linhas de conversations
     * @return list<array<string, mixed>>
     */
    public static function conversationItems(Collection $rows): array
    {
        $lastIds = $rows->pluck('last_message_id')->filter()->all();

        $last = DB::table('messages')
            ->leftJoin('participants', 'participants.id', '=', 'messages.participant_id')
            ->leftJoin('attachments', 'attachments.message_id', '=', 'messages.id')
            ->whereIn('messages.id', $lastIds)
            ->get(['messages.id', 'messages.kind', 'messages.body', 'messages.caption', 'participants.name AS sender', 'participants.is_me', 'attachments.duration_seconds'])
            ->keyBy('id');

        return $rows->map(function ($c) use ($last) {
            $message = $c->last_message_id ? $last->get($c->last_message_id) : null;

            return [
                'id' => (int) $c->id,
                'name' => $c->name,
                'is_group' => (bool) $c->is_group,
                'is_phone' => str_starts_with($c->name, '+'),
                'avatar_index' => (int) $c->avatar_index,
                'preview' => self::preview($message, (bool) $c->is_group, (int) $c->id),
                'last_message_at' => self::time($c->last_message_at),
            ];
        })->values()->all();
    }

    /**
     * Resumo da última mensagem que não é aviso, com "Você: " ou o primeiro nome em grupos.
     */
    public static function preview(?object $message, bool $isGroup, int $conversationId): string
    {
        if ($message === null) {
            // Só avisos: mostra o último deles, como no protótipo.
            $notice = DB::table('messages')->where('conversation_id', $conversationId)
                ->orderByDesc('sent_at')->orderByDesc('id')->value('body');

            return $notice === null ? 'Nenhuma mensagem' : str_replace("\n", ' ', $notice);
        }

        $summary = self::summary($message->kind, $message->body, $message->caption, $message->duration_seconds ?? null);

        if ($isGroup && $message->sender !== null) {
            $who = $message->is_me ? 'Você' : self::firstName($message->sender);
            $summary = "{$who}: {$summary}";
        }

        return $summary;
    }

    public static function summary(string $kind, ?string $body, ?string $caption, ?int $duration): string
    {
        return match ($kind) {
            Message::TEXT => (string) preg_replace('~\n+~', ' ', (string) $body),
            Message::IMAGE => $caption ? 'Foto: '.$caption : 'Foto',
            Message::VIDEO => $caption ? 'Vídeo: '.$caption : 'Vídeo',
            Message::AUDIO => $duration !== null ? 'Áudio ('.self::duration($duration).')' : 'Áudio',
            Message::DOC => (string) $body,
            Message::STICKER => 'Figurinha',
            Message::MISSING => 'Mídia não incluída',
            Message::DELETED => 'Mensagem apagada',
            default => (string) $body,
        };
    }

    public static function duration(int $seconds): string
    {
        return intdiv($seconds, 60).':'.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
    }

    public static function firstName(string $name): string
    {
        return explode(' ', trim($name))[0];
    }

    /**
     * Cor de cada remetente: ((posição entre os que não são você) * 3 + avatar_index) % 8.
     *
     * @return array<int, int> participant_id => slot
     */
    public static function colorSlots(int $conversationId, int $avatarIndex): array
    {
        $ids = DB::table('participants')->where('conversation_id', $conversationId)
            ->where('is_me', false)->orderBy('id')->pluck('id');

        return $ids->mapWithKeys(fn ($id, $position) => [$id => ($position * 3 + $avatarIndex) % 8])->all();
    }

    /**
     * @param  Collection<int, object>  $rows  mensagens com colunas de participante e anexo
     * @param  array<int, int>  $slots
     * @return list<array<string, mixed>>
     */
    public static function messages(Collection $rows, array $slots): array
    {
        return $rows->map(fn ($m) => self::message($m, $slots))->values()->all();
    }

    /**
     * @param  array<int, int>  $slots
     * @return array<string, mixed>
     */
    public static function message(object $m, array $slots): array
    {
        $hasSender = $m->participant_id !== null && ! in_array($m->kind, ConversationStats::NOT_COUNTED, true);

        return [
            'id' => (int) $m->id,
            'sent_at' => self::time($m->sent_at),
            'kind' => $m->kind,
            'sender' => $hasSender ? [
                'participant_id' => (int) $m->participant_id,
                'name' => $m->sender_name,
                'is_me' => (bool) $m->sender_is_me,
                'color_slot' => $m->sender_is_me ? null : ($slots[$m->participant_id] ?? 0),
            ] : null,
            'text' => in_array($m->kind, [Message::TEXT, Message::SYSTEM, Message::CALL], true) ? $m->body : null,
            'caption' => $m->caption,
            'system_icon' => $m->system_icon,
            'attachment' => $m->attachment_id === null ? null : [
                'id' => (int) $m->attachment_id,
                'name' => $m->attachment_name,
                'ext' => $m->attachment_ext,
                'size_bytes' => (int) $m->attachment_size,
                'width' => $m->attachment_width === null ? null : (int) $m->attachment_width,
                'height' => $m->attachment_height === null ? null : (int) $m->attachment_height,
                'duration' => $m->attachment_duration === null ? null : (int) $m->attachment_duration,
                'pages' => $m->attachment_pages === null ? null : (int) $m->attachment_pages,
                'url' => '/media/'.$m->attachment_id,
                'thumb_url' => $m->attachment_thumb !== null || in_array($m->kind, [Message::IMAGE, Message::STICKER], true)
                    ? '/media/'.$m->attachment_id.'/thumb' : null,
            ],
        ];
    }

    /** Colunas usadas por message(). */
    public static function messageColumns(): array
    {
        return [
            'messages.id', 'messages.sent_at', 'messages.kind', 'messages.body', 'messages.caption', 'messages.system_icon', 'messages.participant_id',
            'participants.name AS sender_name', 'participants.is_me AS sender_is_me',
            'attachments.id AS attachment_id', 'attachments.original_name AS attachment_name', 'attachments.ext AS attachment_ext',
            'attachments.size_bytes AS attachment_size', 'attachments.width AS attachment_width', 'attachments.height AS attachment_height',
            'attachments.duration_seconds AS attachment_duration', 'attachments.pages AS attachment_pages', 'attachments.thumb_path AS attachment_thumb',
        ];
    }
}
