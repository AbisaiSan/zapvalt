<?php

namespace App\Import\Media;

/**
 * Arquivos de mídia que acompanham o texto de uma conversa.
 */
interface MediaSource
{
    public function has(string $fileName): bool;

    /** @return resource */
    public function open(string $fileName);
}
