<?php

namespace App\Import\Media;

use RuntimeException;

/**
 * Um .txt sozinho: nenhum anexo disponível.
 */
class NoMedia implements MediaSource
{
    public function has(string $fileName): bool
    {
        return false;
    }

    public function open(string $fileName)
    {
        throw new RuntimeException('Sem mídia disponível.');
    }
}
