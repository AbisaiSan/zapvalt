<?php

namespace App\Jobs;

use App\Archive\BackupWriter;
use App\Models\Backup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Gera o backup pedido pela tela Configurações.
 */
class CreateBackup implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /** Acervos grandes podem levar muitos minutos. */
    public int $timeout = 0;

    public function __construct(public int $backupId) {}

    public function handle(): void
    {
        $backup = Backup::query()->find($this->backupId);

        if ($backup !== null && $backup->status === Backup::WAITING) {
            BackupWriter::make()->run($backup);
        }
    }
}
