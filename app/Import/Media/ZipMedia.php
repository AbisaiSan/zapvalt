<?php

namespace App\Import\Media;

use RuntimeException;
use ZipArchive;

/**
 * Mídia dentro do mesmo .zip do texto. Procura pelo nome do arquivo, preferindo a pasta do texto.
 */
class ZipMedia implements MediaSource
{
    /** @var array<string, string> nome do arquivo => entrada do zip */
    private array $index = [];

    public function __construct(private readonly ZipArchive $zip, string $textEntry)
    {
        $dir = dirname($textEntry);
        $sameDir = [];
        $other = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (str_ends_with($name, '/') || str_starts_with($name, '__MACOSX/') || $name === $textEntry) {
                continue;
            }

            $base = basename($name);
            if (dirname($name) === $dir) {
                $sameDir[$base] ??= $name;
            } else {
                $other[$base] ??= $name;
            }
        }

        $this->index = $sameDir + $other;
    }

    public function has(string $fileName): bool
    {
        return isset($this->index[basename($fileName)]);
    }

    public function open(string $fileName)
    {
        $entry = $this->index[basename($fileName)] ?? null;
        $stream = $entry === null ? false : $this->zip->getStream($entry);

        if ($stream === false) {
            throw new RuntimeException('Entrada de mídia indisponível no pacote.');
        }

        return $stream;
    }
}
