<?php

namespace App\Archive;

use App\Models\Backup;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Gera {DATA}/backups/ZapVault-backup-AAAA-MM-DD.zip com uma cópia consistente do banco (VACUUM INTO),
 * a mídia e as miniaturas. A mídia entra sem recompressão. Importações em andamento ficam de fora.
 */
class BackupWriter
{
    public const MANIFEST = 'zapvault-backup.json';

    public const DATABASE_ENTRY = 'db/zapvault.sqlite';

    /** Um backup "em andamento" mais velho que isso foi interrompido (o worker parou). */
    private const STALE_HOURS = 6;

    public function __construct(private readonly string $root) {}

    public static function make(): self
    {
        return new self(config('zapvault.data_path'));
    }

    public static function active(): ?Backup
    {
        return Backup::query()
            ->whereIn('status', [Backup::WAITING, Backup::RUNNING])
            ->where('started_at', '>', now()->subHours(self::STALE_HOURS))
            ->latest('id')
            ->first();
    }

    /**
     * @param  (callable(int): void)|null  $progress  porcentagem de 0 a 100
     */
    public function run(Backup $backup, ?callable $progress = null): Backup
    {
        $work = "{$this->root}/backups/.backup-{$backup->id}";
        $percent = -1;
        $report = function (int $value) use ($backup, $progress, &$percent) {
            if ($value === $percent) {
                return;
            }
            $percent = $value;
            $backup->update(['percent' => $value]);
            if ($progress !== null) {
                $progress($value);
            }
        };

        $backup->update(['status' => Backup::RUNNING, 'percent' => 0, 'error' => null, 'started_at' => $backup->started_at ?? now()]);

        try {
            File::deleteDirectory($work);
            File::ensureDirectoryExists($work, 0700);

            $snapshot = "{$work}/zapvault.sqlite";
            DB::statement('VACUUM INTO ?', [$snapshot]);
            $counts = $this->trimSnapshot($snapshot);
            $report(2);

            [$files, $total] = $this->mediaFiles($snapshot);
            $total += filesize($snapshot);

            $free = @disk_free_space("{$this->root}/backups");
            if ($free !== false && $free < $total * 1.05) {
                throw new RuntimeException('Não há espaço livre suficiente no disco para gerar o backup.');
            }

            $part = "{$work}/backup.zip";
            $zip = new ZipArchive;
            if ($zip->open($part, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Não foi possível criar o arquivo de backup.');
            }

            $zip->addFromString(self::MANIFEST, (string) json_encode([
                'app' => 'ZapVault',
                'version' => 1,
                'created_at' => now()->format('Y-m-d\TH:i:s'),
                ...$counts,
            ], JSON_PRETTY_PRINT));
            $zip->addFile($snapshot, self::DATABASE_ENTRY);

            // Bytes acumulados ao fim de cada entrada, para o progresso andar por bytes, não por arquivos.
            $done = [0, filesize($snapshot)];
            foreach ($files as $relative => $size) {
                $zip->addFile("{$this->root}/{$relative}", $relative);
                $zip->setCompressionName($relative, ZipArchive::CM_STORE);
                $done[] = end($done) + $size;
            }

            $entries = count($done);
            $zip->registerProgressCallback(0.005, function (float $fraction) use ($report, $done, $entries, $total) {
                $index = min($entries - 1, (int) floor($fraction * $entries));
                $report(2 + (int) floor(97 * $done[$index] / max(1, $total)));
            });

            if (! $zip->close()) {
                throw new RuntimeException('Não foi possível gravar o backup. Algum arquivo mudou durante a cópia; tente de novo.');
            }

            $name = $this->fileName();
            $target = "{$this->root}/backups/{$name}";
            rename($part, $target);
            @chmod($target, 0600);
            $size = (int) filesize($target);

            $backup->update([
                'status' => Backup::DONE,
                'percent' => 100,
                'file_path' => "backups/{$name}",
                'size_bytes' => $size,
                'finished_at' => now(),
            ]);
            Setting::put('last_backup', ['at' => now()->format('Y-m-d\TH:i:s'), 'file_name' => $name, 'size_bytes' => $size]);

            if ($progress !== null) {
                $progress(100);
            }
        } catch (Throwable $e) {
            Log::error('Falha ao gerar backup', ['backup' => $backup->id, 'erro' => $e::class]);
            $message = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível gerar o backup.';
            $backup->update(['status' => Backup::FAILED, 'error' => $message, 'finished_at' => now()]);
        } finally {
            File::deleteDirectory($work);
        }

        return $backup->refresh();
    }

    /**
     * Tira da cópia os rascunhos de importação e os backups em andamento, e conta o que ficou.
     *
     * @return array{conversations: int, messages: int, attachments: int}
     */
    private function trimSnapshot(string $snapshot): array
    {
        $pdo = new PDO('sqlite:'.$snapshot);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        $pdo->beginTransaction();
        $pdo->exec("DELETE FROM import_files WHERE batch_id IN (SELECT id FROM import_batches WHERE status = 'open')");
        $pdo->exec("DELETE FROM import_batches WHERE status = 'open'");
        $pdo->exec("DELETE FROM conversations WHERE status = 'draft'");
        $pdo->exec("DELETE FROM backups WHERE status IN ('waiting', 'running')");
        $pdo->commit();

        $count = fn (string $sql) => (int) $pdo->query($sql)->fetchColumn();

        return [
            'conversations' => $count("SELECT COUNT(*) FROM conversations WHERE status = 'active'"),
            'messages' => $count('SELECT COUNT(*) FROM messages'),
            'attachments' => $count('SELECT COUNT(*) FROM attachments'),
        ];
    }

    /**
     * Mídia e miniaturas usadas pelos anexos da cópia, com o tamanho de cada arquivo.
     *
     * @return array{0: array<string, int>, 1: int}
     */
    private function mediaFiles(string $snapshot): array
    {
        $pdo = new PDO('sqlite:'.$snapshot);
        $rows = $pdo->query('SELECT storage_path FROM attachments UNION SELECT thumb_path FROM attachments WHERE thumb_path IS NOT NULL');
        $root = realpath($this->root);
        $files = [];
        $total = 0;

        foreach ($rows->fetchAll(PDO::FETCH_COLUMN) as $relative) {
            $real = realpath("{$this->root}/{$relative}");

            // Só arquivos dentro da pasta de dados; anexos sem arquivo já aparecem no zapvault:verify.
            if ($real === false || ! str_starts_with($real, $root.DIRECTORY_SEPARATOR) || ! is_file($real)) {
                continue;
            }

            $files[$relative] = (int) filesize($real);
            $total += $files[$relative];
        }

        ksort($files);

        return [$files, $total];
    }

    private function fileName(): string
    {
        $base = 'ZapVault-backup-'.now()->format('Y-m-d');
        $name = "{$base}.zip";

        for ($n = 2; file_exists("{$this->root}/backups/{$name}"); $n++) {
            $name = "{$base}-{$n}.zip";
        }

        return $name;
    }
}
