<?php

namespace App\Import;

use App\Models\Message;
use App\Support\TextNormalizer;
use Illuminate\Support\Facades\DB;

/**
 * Estado acumulado durante a análise de uma conversa: participantes, chaves de duplicidade e contagens.
 */
class AnalysisState
{
    public int $mediaFound = 0;

    public int $mediaMissing = 0;

    /** @var array<string, array{sha256: string, path: string, size: int, ext: string}> */
    public array $stored = [];

    /** @var array<string, array<string, mixed>> */
    public array $described = [];

    /** @var array<string, int> nome normalizado => id */
    private array $participants = [];

    /** @var array<string, int> chave base => quantas vezes já apareceu */
    private array $seen = [];

    /** @var array<string, true> */
    private array $senders = [];

    private bool $groupHint = false;

    /**
     * @param  list<string>  $userNames  nomes do usuário, já normalizados
     */
    public function __construct(
        public readonly int $conversationId,
        public readonly int $importFileId,
        private readonly array $userNames,
    ) {}

    public function observe(ClassifiedMessage $message): void
    {
        if ($message->sender !== null) {
            $this->senders[TextNormalizer::normalize($message->sender)] = true;
        }

        if ($message->kind === Message::SYSTEM && ! $this->groupHint) {
            $text = TextNormalizer::normalize((string) $message->body);
            $this->groupHint = str_contains($text, 'criou o grupo') || str_contains($text, 'adicionou')
                || str_contains($text, 'created group') || str_contains($text, ' added ');
        }
    }

    /** Três ou mais remetentes, ou aviso de criação de grupo ou de pessoa adicionada. */
    public function isGroup(): bool
    {
        return count($this->senders) >= 3 || $this->groupHint;
    }

    /**
     * Hash de (minuto, remetente, tipo, texto, legenda) mais ":n", onde n conta as
     * mensagens idênticas no mesmo minuto. Reimportar o mesmo arquivo gera as mesmas chaves.
     */
    public function dedupeKey(ClassifiedMessage $message): string
    {
        $base = sha1(implode("\x1F", [
            substr($message->sentAt, 0, 16),
            TextNormalizer::normalize((string) $message->sender),
            $message->dedupeKind,
            TextNormalizer::normalize(trim((string) $message->body)),
            TextNormalizer::normalize(trim((string) $message->caption)),
        ]));

        $n = $this->seen[$base] ?? 0;
        $this->seen[$base] = $n + 1;

        return "{$base}:{$n}";
    }

    public function participantId(string $name): int
    {
        $normalized = TextNormalizer::normalize($name);

        if (isset($this->participants[$normalized])) {
            return $this->participants[$normalized];
        }

        DB::table('participants')->insertOrIgnore([
            'conversation_id' => $this->conversationId,
            'name' => $name,
            'name_normalized' => $normalized,
            'is_me' => in_array($normalized, $this->userNames, true),
            'message_count' => 0,
        ]);

        return $this->participants[$normalized] = (int) DB::table('participants')
            ->where('conversation_id', $this->conversationId)
            ->where('name_normalized', $normalized)
            ->value('id');
    }
}
