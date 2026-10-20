<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\ImportBatch;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyCommand extends Command
{
    protected $signature = 'zapvault:verify {--hashes : Confere também o SHA-256 de cada arquivo de mídia}';

    protected $description = 'Confere contagens, índice de busca e arquivos de mídia do acervo';

    /** @var list<string> */
    private array $issues = [];

    public function handle(): int
    {
        $this->checkConversations();
        $this->checkParticipants();
        $this->checkDrafts();
        $this->checkSearchIndex();
        $this->checkAttachments();

        $summary = sprintf(
            '%d conversas, %d mensagens, %d anexos.',
            Conversation::query()->active()->count(),
            DB::table('messages')->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
                ->where('conversations.status', Conversation::ACTIVE)->count(),
            DB::table('attachments')->count(),
        );

        if ($this->issues === []) {
            $this->info("Nenhuma inconsistência encontrada. {$summary}");

            return self::SUCCESS;
        }

        $this->line($summary);
        foreach ($this->issues as $issue) {
            $this->error($issue);
        }
        $this->warn(count($this->issues).' inconsistência(s) encontrada(s).');

        return self::FAILURE;
    }

    private function checkConversations(): void
    {
        $rows = DB::select(<<<'SQL'
            SELECT c.id, c.name, c.message_count, c.first_message_at, c.last_message_at, c.last_message_id,
                (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.kind NOT IN ('system', 'call')) AS real_count,
                (SELECT MIN(sent_at) FROM messages m WHERE m.conversation_id = c.id) AS real_first,
                (SELECT MAX(sent_at) FROM messages m WHERE m.conversation_id = c.id) AS real_last,
                (SELECT m.id FROM messages m WHERE m.conversation_id = c.id AND m.kind NOT IN ('system', 'call')
                    ORDER BY m.sent_at DESC, m.id DESC LIMIT 1) AS real_last_id
            FROM conversations c
            WHERE c.status = 'active'
            SQL);

        foreach ($rows as $row) {
            if ((int) $row->message_count !== (int) $row->real_count) {
                $this->issues[] = "Conversa #{$row->id}: contagem de mensagens {$row->message_count}, esperado {$row->real_count}.";
            }
            if ($row->first_message_at !== $row->real_first || $row->last_message_at !== $row->real_last) {
                $this->issues[] = "Conversa #{$row->id}: datas de primeira ou última mensagem desatualizadas.";
            }
            if ((int) $row->last_message_id !== (int) $row->real_last_id) {
                $this->issues[] = "Conversa #{$row->id}: última mensagem desatualizada.";
            }
        }
    }

    private function checkParticipants(): void
    {
        $wrong = DB::table('participants AS p')
            ->whereRaw('p.message_count <> (SELECT COUNT(*) FROM messages m WHERE m.participant_id = p.id)')
            ->count();

        if ($wrong > 0) {
            $this->issues[] = "{$wrong} participante(s) com contagem de mensagens desatualizada.";
        }
    }

    private function checkDrafts(): void
    {
        $openBatches = ImportBatch::query()->where('status', ImportBatch::OPEN)->pluck('id');

        $orphans = Conversation::query()
            ->where('status', Conversation::DRAFT)
            ->whereNotIn('id', DB::table('import_files')->whereIn('batch_id', $openBatches)->whereNotNull('conversation_id')->select('conversation_id'))
            ->count();

        if ($orphans > 0) {
            $this->issues[] = "{$orphans} rascunho(s) de importação sem lote aberto.";
        }
    }

    private function checkSearchIndex(): void
    {
        $expected = DB::table('messages')->whereNotNull('search_text')->count();
        $indexed = DB::table('messages_fts_docsize')->count();

        if ($expected !== $indexed) {
            $this->issues[] = "Índice de busca com {$indexed} mensagens, esperado {$expected}. Rode php artisan zapvault:reindex.";
        }

        $withoutFile = DB::table('messages')
            ->whereIn('kind', Message::ATTACHMENT_KINDS)
            ->whereNotExists(fn ($q) => $q->from('attachments')->whereColumn('attachments.message_id', 'messages.id'))
            ->count();

        if ($withoutFile > 0) {
            $this->issues[] = "{$withoutFile} mensagem(ns) de mídia sem anexo registrado.";
        }
    }

    private function checkAttachments(): void
    {
        $root = realpath(config('zapvault.data_path'));
        $missing = 0;
        $outside = 0;
        $badHash = 0;

        DB::table('attachments')->select(['id', 'storage_path', 'sha256'])->orderBy('id')
            ->chunkById(1000, function ($attachments) use ($root, &$missing, &$outside, &$badHash) {
                foreach ($attachments as $attachment) {
                    $real = realpath($root.'/'.$attachment->storage_path);

                    if ($real === false) {
                        $missing++;
                    } elseif (! str_starts_with($real, $root.DIRECTORY_SEPARATOR)) {
                        $outside++;
                    } elseif ($this->option('hashes') && hash_file('sha256', $real) !== $attachment->sha256) {
                        $badHash++;
                    }
                }
            });

        if ($missing > 0) {
            $this->issues[] = "{$missing} anexo(s) sem arquivo no disco.";
        }
        if ($outside > 0) {
            $this->issues[] = "{$outside} anexo(s) apontando para fora da pasta de dados.";
        }
        if ($badHash > 0) {
            $this->issues[] = "{$badHash} anexo(s) com conteúdo diferente do hash registrado.";
        }
    }
}
