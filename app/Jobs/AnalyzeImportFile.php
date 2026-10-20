<?php

namespace App\Jobs;

use App\Import\ImportAnalyzer;
use App\Models\ImportFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Analisa um arquivo enviado. A fila processa um arquivo por vez, na ordem de envio.
 */
class AnalyzeImportFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /** Arquivos grandes podem levar muitos minutos. */
    public int $timeout = 0;

    public function __construct(public int $importFileId) {}

    public function handle(ImportAnalyzer $analyzer): void
    {
        $file = ImportFile::query()->find($this->importFileId);

        if ($file !== null && $file->status === ImportFile::WAITING) {
            $analyzer->analyze($file);
        }
    }
}
