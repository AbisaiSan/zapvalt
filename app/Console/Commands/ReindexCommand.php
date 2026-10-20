<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReindexCommand extends Command
{
    protected $signature = 'zapvault:reindex';

    protected $description = 'Recalcula o texto pesquisável das mensagens e reconstrói o índice de busca';

    public function handle(): int
    {
        $total = DB::table('messages')->count();
        $bar = $this->output->createProgressBar($total);
        $changed = 0;

        DB::table('messages')
            ->select(['id', 'kind', 'body', 'caption', 'search_text'])
            ->orderBy('id')
            ->chunkById(2000, function ($messages) use ($bar, &$changed) {
                DB::transaction(function () use ($messages, &$changed) {
                    foreach ($messages as $message) {
                        $text = Message::searchTextFor($message->kind, $message->body, $message->caption);
                        if ($text !== $message->search_text) {
                            DB::table('messages')->where('id', $message->id)->update(['search_text' => $text]);
                            $changed++;
                        }
                    }
                });
                $bar->advance(count($messages));
            });

        $bar->finish();
        $this->newLine();

        DB::transaction(function () {
            DB::statement("INSERT INTO messages_fts (messages_fts) VALUES ('delete-all')");
            DB::statement('INSERT INTO messages_fts (rowid, search_text) SELECT id, search_text FROM messages WHERE search_text IS NOT NULL');
        });

        DB::statement("INSERT INTO messages_fts (messages_fts) VALUES ('optimize')");

        $indexed = DB::table('messages')->whereNotNull('search_text')->count();
        $this->info("Índice de busca reconstruído: {$indexed} mensagens pesquisáveis ({$changed} atualizadas).");

        return self::SUCCESS;
    }
}
