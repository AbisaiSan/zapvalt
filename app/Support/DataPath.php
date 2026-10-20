<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Pasta de dados do ZapVault, fora do projeto e fora do Git.
 */
class DataPath
{
    /** Subpastas criadas automaticamente dentro da pasta de dados. */
    public const SUBDIRS = ['db', 'media', 'thumbs', 'originals', 'backups', 'uploads-tmp'];

    /**
     * Resolve o caminho configurado em ZAPVAULT_DATA_PATH. Vazio usa ~/ZapVault-dados;
     * caminho relativo é resolvido a partir da raiz do projeto (usado nos testes).
     * Chamado apenas nos arquivos de config, por causa do config:cache.
     */
    public static function resolve(?string $configured): string
    {
        $path = trim((string) $configured);

        if ($path === '') {
            $home = $_SERVER['HOME'] ?? getenv('HOME') ?: sys_get_temp_dir();
            $path = rtrim($home, '/').'/ZapVault-dados';
        } elseif (! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        return rtrim($path, '/');
    }

    /**
     * Cria a pasta de dados (permissão 700), as subpastas e os arquivos de banco, se faltarem.
     *
     * @param  array<int, string|null>  $databases
     */
    public static function ensure(string $root, array $databases = []): void
    {
        if (! is_dir($root)) {
            File::ensureDirectoryExists($root, 0700);
            @chmod($root, 0700);
        }

        foreach (self::SUBDIRS as $dir) {
            File::ensureDirectoryExists("{$root}/{$dir}", 0700);
        }

        foreach ($databases as $database) {
            if ($database !== null && str_starts_with($database, $root.'/') && ! file_exists($database)) {
                touch($database);
                @chmod($database, 0600);
            }
        }
    }
}
