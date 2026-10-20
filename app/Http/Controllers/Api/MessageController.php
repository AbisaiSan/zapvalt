<?php

namespace App\Http\Controllers\Api;

use App\Api\Cursor;
use App\Api\Presenter;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Search\MessageSearch;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Janelas de mensagens por cursor (sent_at, id), calendário, busca na conversa e mídia.
 */
class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'around' => ['nullable', 'integer'],
            'day' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $limit = (int) $request->query('limit', 100);
        $focusId = null;

        if ($request->filled('around')) {
            $focus = $this->base($conversation)->where('messages.id', (int) $request->query('around'))->first();
            abort_if($focus === null, 404, 'Mensagem não encontrada nesta conversa.');

            $half = intdiv($limit, 2);
            $older = $this->older($conversation, $focus->sent_at, $focus->id, $half);
            $newer = $this->newer($conversation, $focus->sent_at, $focus->id, $limit - $half - 1);
            $messages = $older->push($focus)->concat($newer);
            $focusId = (int) $focus->id;
        } elseif ($request->filled('day')) {
            $first = $this->base($conversation)
                ->where('messages.sent_at', '>=', $request->query('day').' 00:00:00')
                ->orderBy('messages.sent_at')->orderBy('messages.id')
                ->first();

            if ($first === null) {
                $messages = $this->older($conversation, null, null, $limit);
            } else {
                $messages = collect([$first])->concat($this->newer($conversation, $first->sent_at, $first->id, $limit - 1));
                $focusId = (int) $first->id;
            }
        } elseif (($before = Cursor::decode($request->query('before'))) !== null) {
            $messages = $this->older($conversation, $before[0], $before[1], $limit);
        } elseif (($after = Cursor::decode($request->query('after'))) !== null) {
            $messages = $this->newer($conversation, $after[0], $after[1], $limit);
        } else {
            $messages = $this->older($conversation, null, null, $limit);
        }

        $first = $messages->first();
        $last = $messages->last();

        $response = [
            'messages' => Presenter::messages($messages, Presenter::colorSlots($conversation->id, $conversation->avatar_index)),
            'has_older' => $first !== null && $this->exists($conversation, '<', $first),
            'has_newer' => $last !== null && $this->exists($conversation, '>', $last),
            'older_cursor' => $first ? Cursor::encode($first->sent_at, $first->id) : null,
            'newer_cursor' => $last ? Cursor::encode($last->sent_at, $last->id) : null,
        ];

        if ($focusId !== null) {
            $response['focus_id'] = $focusId;
        }

        return response()->json($response);
    }

    /** Dias com mensagens, para "Pular para data". */
    public function days(Conversation $conversation): JsonResponse
    {
        $days = DB::table('messages')
            ->where('conversation_id', $conversation->id)
            // Como no protótipo: avisos e chamadas não contam, mas o dia aparece (com 0).
            ->selectRaw("substr(sent_at, 1, 10) AS day, SUM(kind NOT IN ('system', 'call')) AS count")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => ['day' => $row->day, 'count' => (int) $row->count]);

        return response()->json(['days' => $days]);
    }

    /** Busca dentro da conversa: mensagens em ordem cronológica e ocorrências de cada uma. */
    public function matches(Request $request, Conversation $conversation): JsonResponse
    {
        $term = MessageSearch::term($request->query('q'));

        if ($term === null) {
            return response()->json(['total' => 0, 'matches' => []]);
        }

        $matches = MessageSearch::query($term, $conversation->id)
            ->orderBy('messages.sent_at')->orderBy('messages.id')
            ->get(['messages.id', 'messages.sent_at', 'messages.search_text'])
            ->map(fn ($m) => [
                'message_id' => (int) $m->id,
                'sent_at' => Presenter::time($m->sent_at),
                'count' => max(1, MessageSearch::occurrences($m->search_text, $term)),
            ]);

        return response()->json(['total' => $matches->sum('count'), 'matches' => $matches]);
    }

    /** Imagens e vídeos da conversa, em ordem, para o visualizador. */
    public function media(Conversation $conversation): JsonResponse
    {
        $items = DB::table('messages')
            ->join('attachments', 'attachments.message_id', '=', 'messages.id')
            ->leftJoin('participants', 'participants.id', '=', 'messages.participant_id')
            ->where('messages.conversation_id', $conversation->id)
            ->whereIn('messages.kind', [Message::IMAGE, Message::VIDEO])
            ->orderBy('messages.sent_at')->orderBy('messages.id')
            ->get([
                'messages.id AS message_id', 'messages.kind', 'messages.sent_at', 'messages.caption',
                'participants.name AS sender', 'participants.is_me',
                'attachments.id AS attachment_id', 'attachments.original_name', 'attachments.width', 'attachments.height',
                'attachments.duration_seconds', 'attachments.thumb_path',
            ])
            ->map(fn ($m) => [
                'message_id' => (int) $m->message_id,
                'attachment_id' => (int) $m->attachment_id,
                'kind' => $m->kind,
                'sender' => $m->sender === null ? null : ['name' => $m->sender, 'is_me' => (bool) $m->is_me],
                'sent_at' => Presenter::time($m->sent_at),
                'caption' => $m->caption,
                'name' => $m->original_name,
                'width' => $m->width === null ? null : (int) $m->width,
                'height' => $m->height === null ? null : (int) $m->height,
                'duration' => $m->duration_seconds === null ? null : (int) $m->duration_seconds,
                'url' => '/media/'.$m->attachment_id,
                'thumb_url' => $m->kind === Message::IMAGE || $m->thumb_path !== null ? '/media/'.$m->attachment_id.'/thumb' : null,
            ]);

        return response()->json(['items' => $items]);
    }

    private function base(Conversation $conversation): Builder
    {
        return DB::table('messages')
            ->leftJoin('participants', 'participants.id', '=', 'messages.participant_id')
            ->leftJoin('attachments', 'attachments.message_id', '=', 'messages.id')
            ->where('messages.conversation_id', $conversation->id)
            ->select(Presenter::messageColumns());
    }

    /** Até $limit mensagens anteriores ao par (sent_at, id), em ordem cronológica. */
    private function older(Conversation $conversation, ?string $at, ?int $id, int $limit): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        $query = $this->base($conversation)->orderByDesc('messages.sent_at')->orderByDesc('messages.id')->limit($limit);

        if ($at !== null) {
            $query->whereRaw('(messages.sent_at, messages.id) < (?, ?)', [$at, $id]);
        }

        return $query->get()->reverse()->values();
    }

    /** Até $limit mensagens posteriores ao par (sent_at, id), em ordem cronológica. */
    private function newer(Conversation $conversation, string $at, int $id, int $limit): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        return $this->base($conversation)
            ->whereRaw('(messages.sent_at, messages.id) > (?, ?)', [$at, $id])
            ->orderBy('messages.sent_at')->orderBy('messages.id')
            ->limit($limit)
            ->get();
    }

    private function exists(Conversation $conversation, string $operator, object $message): bool
    {
        return DB::table('messages')
            ->where('conversation_id', $conversation->id)
            ->whereRaw("(sent_at, id) {$operator} (?, ?)", [$message->sent_at, $message->id])
            ->exists();
    }
}
