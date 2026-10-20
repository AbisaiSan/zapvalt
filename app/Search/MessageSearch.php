<?php

namespace App\Search;

use App\Models\Conversation;
use App\Support\TextNormalizer;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Busca no servidor: ignora maiúsculas e acentos e aceita trecho de palavra e frase.
 * Três ou mais caracteres usam o índice FTS5 (trigramas); exatamente dois usam LIKE.
 */
class MessageSearch
{
    public const MAX_TERM = 200;

    public const COUNT_CAP = 10_000;

    /** Termo normalizado, sem espaços nas pontas e com no máximo 200 caracteres; nulo se tiver menos de 2. */
    public static function term(?string $q): ?string
    {
        $term = mb_substr(TextNormalizer::normalize(trim((string) $q)), 0, self::MAX_TERM);

        return mb_strlen($term) >= 2 ? $term : null;
    }

    /**
     * Mensagens que casam com o termo, só de conversas ativas.
     */
    public static function query(string $term, ?int $conversationId = null): Builder
    {
        $query = DB::table('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.status', Conversation::ACTIVE);

        if ($conversationId !== null) {
            $query->where('messages.conversation_id', $conversationId);
        }

        if (mb_strlen($term) >= 3) {
            $query->whereIn('messages.id', fn ($q) => $q->from('messages_fts')
                ->select('rowid')
                ->whereRaw('messages_fts MATCH ?', [TextNormalizer::ftsPhrase($term)]));
        } else {
            $query->whereRaw("messages.search_text LIKE ? ESCAPE '\\'", ['%'.TextNormalizer::likeEscape($term).'%']);
        }

        return $query;
    }

    /**
     * Total de mensagens (com teto, para termos comuns não travarem a tela) e de conversas com resultado.
     *
     * @return array{total: int, conversations: int, capped: bool}
     */
    public static function counts(string $term): array
    {
        // Uma passada só: resultados por conversa.
        $perConversation = DB::query()
            ->fromSub(self::query($term)->select(['messages.conversation_id']), 'hits')
            ->groupBy('conversation_id')
            ->selectRaw('COUNT(*) AS n')
            ->pluck('n');

        $total = (int) $perConversation->sum();

        return [
            'total' => min($total, self::COUNT_CAP),
            'conversations' => $perConversation->count(),
            'capped' => $total > self::COUNT_CAP,
        ];
    }

    /** Ocorrências do termo sem sobreposição. */
    public static function occurrences(?string $searchText, string $term): int
    {
        return $searchText === null ? 0 : substr_count($searchText, $term);
    }
}
