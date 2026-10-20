<?php

namespace App\Archive;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * Restaura um backup do ZapVault para a pasta de dados configurada, que precisa estar vazia.
 */
class BackupRestorer
{
    /** Entradas aceitas: o manifesto, o banco e arquivos de mídia e miniaturas nomeados pelo hash. */
    private const ENTRY = '~^(zapvault-backup\.json|db/zapvault\.sqlite|(media|thumbs)/[0-9a-f]{2}/[0-9a-f]{2}/[0-9a-f]{64}\.[a-z0-9]{1,8})$~';

    public function __construct(private readonly string $root) {}

    public static function make(): self
    {
        return new self(config('zapvault.data_path'));
    }

    /**
     * @param  (callable(int): void)|null  $progress  porcentagem de 0 a 100
     * @return array{conversations: int, messages: int, attachments: int}
     */
    public function restore(string $file, ?callable $progress = null): array
    {
        if (! is_file($file)) {
            throw new RuntimeException('Arquivo de backup não encontrado.');
        }

        $this->ensureEmpty();

        $zip = new ZipArchive;
        if ($zip->open($file, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Este arquivo não é um backup do ZapVault.');
        }

        try {
            [$entries, $total] = $this->entries($zip);

            $free = @disk_free_space($this->root);
            if ($free !== false && $free < $total * 1.05) {
                throw new RuntimeException('Não há espaço livre suficiente no disco para restaurar o backup.');
            }

            $database = (string) config('database.connections.sqlite.database');
            DB::disconnect('sqlite');
            foreach (['', '-wal', '-shm'] as $suffix) {
                @unlink($database.$suffix);
            }

            $written = 0;
            foreach ($entries as $index => $name) {
                $target = $name === BackupWriter::DATABASE_ENTRY ? $database : "{$this->root}/{$name}";
                $written += $this->extract($zip, $index, $target);
                if ($progress !== null) {
                    $progress((int) floor(95 * $written / max(1, $total)));
                }
            }
        } finally {
            $zip->close();
        }

        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        $this->ensureQueueTables();

        if ($progress !== null) {
            $progress(100);
        }

        return [
            'conversations' => DB::table('conversations')->where('status', 'active')->count(),
            'messages' => DB::table('messages')->count(),
            'attachments' => DB::table('attachments')->count(),
        ];
    }

    /** Recusa restaurar por cima de um acervo que já tem conversas ou arquivos de mídia. */
    private function ensureEmpty(): void
    {
        $hasConversations = Schema::hasTable('conversations') && DB::table('conversations')->exists();
        $hasMedia = $this->hasFiles("{$this->root}/media") || $this->hasFiles("{$this->root}/thumbs");

        if ($hasConversations || $hasMedia) {
            throw new RuntimeException(
                "A pasta de dados {$this->root} já tem conversas ou anexos. A restauração só é feita em uma pasta vazia, para não sobrescrever nada. "
                .'Aponte ZAPVAULT_DATA_PATH, no .env, para uma pasta nova e rode o comando de novo.'
            );
        }
    }

    /**
     * @return array{0: array<int, string>, 1: int}
     */
    private function entries(ZipArchive $zip): array
    {
        $entries = [];
        $total = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = (string) ($stat['name'] ?? '');

            if (str_ends_with($name, '/')) {
                continue;
            }
            if (! preg_match(self::ENTRY, $name)) {
                throw new RuntimeException('Este arquivo não é um backup do ZapVault (contém arquivos inesperados).');
            }

            $entries[$i] = $name;
            $total += (int) $stat['size'];
        }

        if (! in_array(BackupWriter::MANIFEST, $entries, true) || ! in_array(BackupWriter::DATABASE_ENTRY, $entries, true)) {
            throw new RuntimeException('Este arquivo não é um backup do ZapVault.');
        }

        return [$entries, $total];
    }

    private function extract(ZipArchive $zip, int $index, string $target): int
    {
        if (str_ends_with($target, BackupWriter::MANIFEST)) {
            return 0;
        }

        File::ensureDirectoryExists(dirname($target), 0700);

        $in = $zip->getStream($zip->getNameIndex($index));
        $out = fopen($target, 'wb');
        if ($in === false || $out === false) {
            throw new RuntimeException('Não foi possível ler o backup. O arquivo pode estar corrompido.');
        }

        $bytes = stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);
        @chmod($target, 0600);

        if ($bytes === false) {
            throw new RuntimeException('Não foi possível ler o backup. O arquivo pode estar corrompido.');
        }

        return $bytes;
    }

    private function hasFiles(string $dir): bool
    {
        if (! is_dir($dir)) {
            return false;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($files as $file) {
            return true;
        }

        return false;
    }

    /** As filas ficam em outro arquivo, que o backup não leva: cria as tabelas se faltarem. */
    private function ensureQueueTables(): void
    {
        if (Schema::connection('queue')->hasTable('jobs')) {
            return;
        }

        $migration = require database_path('migrations/2026_10_20_000000_create_queue_tables.php');
        $migration->up();
    }
}
