<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateCommand extends Command
{
    protected $signature = 'zapvault:migrate';

    protected $description = 'Atualiza o banco depois de uma nova versão, guardando antes uma cópia (VACUUM INTO)';

    public function handle(): int
    {
        // Instalação nova: não há acervo para copiar.
        if (! Schema::hasTable('migrations')) {
            return $this->call('migrate', ['--force' => true]);
        }

        $pending = collect($this->getLaravel()['migrator']->getMigrationFiles(database_path('migrations')))
            ->keys()
            ->diff(DB::table('migrations')->pluck('migration'));

        if ($pending->isEmpty()) {
            $this->info('O banco já está atualizado.');

            return self::SUCCESS;
        }

        $copy = config('zapvault.data_path').'/backups/antes-de-atualizar-'.now()->format('Y-m-d-His').'.sqlite';
        DB::statement('VACUUM INTO ?', [$copy]);
        @chmod($copy, 0600);
        $this->line("Cópia do banco guardada em {$copy}.");

        return $this->call('migrate', ['--force' => true]);
    }
}
