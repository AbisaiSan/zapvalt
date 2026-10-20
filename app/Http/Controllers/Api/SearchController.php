<?php

namespace App\Http\Controllers\Api;

use App\Api\Cursor;
use App\Api\Presenter;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Search\MessageSearch;
use App\Support\TextNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /** Conversas pelo nome, da mais recente para a mais antiga. */
    public function conversations(Request $request): JsonResponse
    {
        $term = MessageSearch::term($request->query('q'));

        if ($term === null) {
            return response()->json(['items' => [], 'total' => 0]);
        }

        $query = DB::table('conversations')
            ->where('status', Conversation::ACTIVE)
            ->whereRaw("name_normalized LIKE ? ESCAPE '\\'", ['%'.TextNormalizer::likeEscape($term).'%']);

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('last_message_at')->orderByDesc('id')->limit(200)->get();

        return response()->json(['items' => Presenter::conversationItems($rows), 'total' => $total]);
    }

    /** Mensagens pelo conteúdo, da mais recente para a mais antiga, 50 por página. */
    public function messages(Request $request): JsonResponse
    {
        $term = MessageSearch::term($request->query('q'));

        if ($term === null) {
            return response()->json(['results' => [], 'total' => 0, 'total_capped' => false, 'conversations' => 0, 'next_cursor' => null]);
        }

        $limit = max(1, min(100, (int) $request->query('limit', 50)));
        $cursor = Cursor::decode($request->query('cursor'));

        // Primeiro só os ids na ordem (leve para ordenar), depois os detalhes da página.
        $query = MessageSearch::query($term)
            ->orderByDesc('messages.sent_at')->orderByDesc('messages.id')
            ->limit($limit + 1)
            ->select(['messages.id']);

        if ($cursor !== null) {
            $query->whereRaw('(messages.sent_at, messages.id) < (?, ?)', $cursor);
        }

        $ids = $query->pluck('messages.id');

        $rows = DB::table('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->leftJoin('participants', 'participants.id', '=', 'messages.participant_id')
            ->whereIn('messages.id', $ids)
            ->orderByDesc('messages.sent_at')->orderByDesc('messages.id')
            ->get([
                'messages.id', 'messages.conversation_id', 'messages.sent_at', 'messages.kind', 'messages.body', 'messages.caption',
                'conversations.name AS conversation_name', 'conversations.is_group',
                'participants.name AS sender', 'participants.is_me',
            ]);

        $more = $rows->count() > $limit;
        $rows = $rows->take($limit);
        $last = $rows->last();
        $counts = $cursor === null ? MessageSearch::counts($term) : null;

        $response = [
            'results' => $rows->map(fn ($m) => $this->result($m))->values(),
            'next_cursor' => $more && $last ? Cursor::encode($last->sent_at, $last->id) : null,
        ];

        // Contagens só na primeira página; as seguintes mantêm as que o navegador já tem.
        if ($counts !== null) {
            $response += ['total' => $counts['total'], 'total_capped' => $counts['capped'], 'conversations' => $counts['conversations']];
        }

        return response()->json($response);
    }

    /** @return array<string, mixed> */
    private function result(object $m): array
    {
        [$field, $text] = match ($m->kind) {
            Message::DOC => ['file', $m->body],
            Message::IMAGE, Message::VIDEO => ['caption', $m->caption],
            default => ['text', $m->body],
        };

        $who = match (true) {
            $m->sender === null => null,
            (bool) $m->is_me => 'Você',
            (bool) $m->is_group => Presenter::firstName($m->sender),
            default => null,
        };

        return [
            'message_id' => (int) $m->id,
            'conversation_id' => (int) $m->conversation_id,
            'conversation_name' => $m->conversation_name,
            'sent_at' => Presenter::time($m->sent_at),
            'who' => $who,
            'field' => $field,
            'kind' => $m->kind,
            'text' => mb_substr((string) $text, 0, 4000),
        ];
    }
}
