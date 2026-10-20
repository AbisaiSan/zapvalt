<?php

namespace App\Console\Commands;

use App\Archive\BackupWriter;
use App\Models\Backup;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    protected $signature = 'zapvault:backup';

    protected $description = 'Gera um backup .zip do acervo (banco, mídia e miniaturas) na pasta backups';

    public function handle(): int
    {
        if (BackupWriter::active() !== null) {
            $this->error('Já há um backup em andamento. Aguarde ele terminar.');

            return self::FAILURE;
        }

        $backup = Backup::query()->create(['status' => Backup::WAITING, 'percent' => 0, 'started_at' => now()]);

        $bar = $this->output->createProgressBar(100);
        $bar->start();
        $backup = BackupWriter::make()->run($backup, fn (int $percent) => $bar->setProgress($percent));
        $bar->finish();
        $this->newLine(2);

        if ($backup->status !== Backup::DONE) {
            $this->error((string) $backup->error);

            return self::FAILURE;
        }

        $path = config('zapvault.data_path').'/'.$backup->file_path;
        $this->info("Backup concluído: {$path} (".number_format($backup->size_bytes / 1024 ** 2, 1, ',', '.').' MB).');
        $this->line('Copie o arquivo para um disco externo, de preferência criptografado.');

        return self::SUCCESS;
    }
}
