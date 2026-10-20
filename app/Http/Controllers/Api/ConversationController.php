<?php

namespace App\Http\Controllers\Api;

use App\Api\Cursor;
use App\Api\Presenter;
use App\Archive\ConversationRemover;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Support\TextNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /** Lista de conversas, da última mensagem mais recente para a mais antiga. */
    public function index(Request $request): JsonResponse
    {
        $limit = $this->limit($request, 100, 200);
        $cursor = Cursor::decode($request->query('cursor'));

        $query = DB::table('conversations')
            ->where('status', Conversation::ACTIVE)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit($limit + 1);

        if ($cursor !== null) {
            [$at, $id] = $cursor;
            $query->whereRaw('(COALESCE(last_message_at, \'\'), id) < (?, ?)', [$at, $id]);
        }

        $rows = $query->get();
        $more = $rows->count() > $limit;
        $rows = $rows->take($limit);
        $last = $rows->last();

        return response()->json([
            'items' => Presenter::conversationItems($rows),
            'next_cursor' => $more && $last ? Cursor::encode($last->last_message_at, $last->id) : null,
        ]);
    }

    /** Cabeçalho da conversa, com participantes. */
    public function show(Conversation $conversation): JsonResponse
    {
        $slots = Presenter::colorSlots($conversation->id, $conversation->avatar_index);

        $participants = DB::table('participants')
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('is_me')->orderBy('id')
            ->get()
            ->map(fn ($p) => [
                'id' => (int) $p->id,
                'name' => $p->name,
                'is_me' => (bool) $p->is_me,
                'message_count' => (int) $p->message_count,
                'color_slot' => $p->is_me ? null : ($slots[$p->id] ?? 0),
            ]);

        return response()->json([
            'id' => $conversation->id,
            'name' => $conversation->name,
            'is_group' => $conversation->is_group,
            'is_phone' => str_starts_with($conversation->name, '+'),
            'avatar_index' => $conversation->avatar_index,
            'first_message_at' => Presenter::time($conversation->getRawOriginal('first_message_at')),
            'last_message_at' => Presenter::time($conversation->getRawOriginal('last_message_at')),
            'message_count' => $conversation->message_count,
            'participant_count' => $participants->count(),
            'source_file_name' => $conversation->source_file_name,
            'participants' => $participants,
        ]);
    }

    public function update(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:120']],
            ['name.required' => 'Informe um nome para a conversa.', 'name.max' => 'Use no máximo 120 caracteres.'],
        );

        $name = trim($data['name']);
        abort_if($name === '', 422, 'Informe um nome para a conversa.');

        $conversation->update(['name' => $name, 'name_normalized' => TextNormalizer::normalize($name)]);

        $row = DB::table('conversations')->where('id', $conversation->id)->get();

        return response()->json(Presenter::conversationItems($row)[0]);
    }

    public function destroy(Conversation $conversation, ConversationRemover $remover): Response
    {
        $remover->delete($conversation);

        return response()->noContent();
    }

    private function limit(Request $request, int $default, int $max): int
    {
        return max(1, min($max, (int) $request->query('limit', $default)));
    }
}
