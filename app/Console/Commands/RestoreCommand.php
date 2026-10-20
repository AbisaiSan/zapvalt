<?php

namespace App\Console\Commands;

use App\Archive\BackupRestorer;
use Illuminate\Console\Command;
use RuntimeException;

class RestoreCommand extends Command
{
    protected $signature = 'zapvault:restore {arquivo : Backup .zip gerado pelo ZapVault}';

    protected $description = 'Restaura um backup para uma pasta de dados vazia (ZAPVAULT_DATA_PATH)';

    public function handle(): int
    {
        $this->line('Restaurando em '.config('zapvault.data_path').'…');
        $bar = $this->output->createProgressBar(100);

        try {
            $bar->start();
            $counts = BackupRestorer::make()->restore((string) $this->argument('arquivo'), fn (int $percent) => $bar->setProgress($percent));
            $bar->finish();
            $this->newLine(2);
        } catch (RuntimeException $e) {
            $this->newLine(2);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Backup restaurado: %s conversas, %s mensagens e %s anexos.',
            number_format($counts['conversations'], 0, ',', '.'),
            number_format($counts['messages'], 0, ',', '.'),
            number_format($counts['attachments'], 0, ',', '.'),
        ));

        return $this->call('zapvault:verify', ['--hashes' => true]);
    }
}
