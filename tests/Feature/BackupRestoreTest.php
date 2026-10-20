<?php

use App\Models\Backup;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// Sem RefreshDatabase: VACUUM INTO não roda dentro de transação. Cada teste usa um banco em arquivo
// dentro de uma pasta de dados temporária.

/** @return array{counts: array<string, int>, files: array<string, string>} */
function archiveFingerprint(string $root): array
{
    $files = [];
    foreach (['media', 'thumbs'] as $dir) {
        foreach (File::allFiles("{$root}/{$dir}") as $file) {
            $files[$dir.'/'.$file->getRelativePathname()] = hash_file('sha256', $file->getPathname());
        }
    }
    ksort($files);

    return [
        'counts' => [
            'conversations' => DB::table('conversations')->count(),
            'messages' => DB::table('messages')->count(),
            'participants' => DB::table('participants')->count(),
            'attachments' => DB::table('attachments')->count(),
            'fts' => DB::table('messages_fts_docsize')->count(),
        ],
        'files' => $files,
    ];
}

function seedFileDemo(): void
{
    Carbon::setTestNow('2026-10-20 12:00:00');
    test()->artisan('zapvault:demo')->assertSuccessful();
    Carbon::setTestNow();
}

test('o backup pela API gera o .zip com banco, mídia e miniaturas, e restaurar em pasta vazia mantém tudo', function () {
    $source = useFileArchive();
    seedFileDemo();
    $before = archiveFingerprint($source);

    Carbon::setTestNow('2026-10-20 18:04:00');
    $response = $this->postJson('/api/backups')->assertStatus(202);
    $backup = Backup::query()->findOrFail($response->json('id'));

    // A fila é síncrona nos testes: o backup já terminou.
    $this->getJson("/api/backups/{$backup->id}")->assertOk()
        ->assertJsonPath('status', 'done')
        ->assertJsonPath('percent', 100)
        ->assertJsonPath('file_name', 'ZapVault-backup-2026-10-20.zip');

    $zipPath = "{$source}/backups/ZapVault-backup-2026-10-20.zip";
    expect($zipPath)->toBeFile()
        ->and(Setting::get('last_backup'))->toBe(['at' => '2026-10-20T18:04:00', 'file_name' => 'ZapVault-backup-2026-10-20.zip', 'size_bytes' => filesize($zipPath)])
        ->and(File::directories("{$source}/backups"))->toBe([]);
    $this->getJson('/api/settings')->assertJsonPath('last_backup.file_name', 'ZapVault-backup-2026-10-20.zip')->assertJsonPath('current_backup', null);

    $zip = new ZipArchive;
    $zip->open($zipPath);
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $names[] = $stat['name'];
        if (str_starts_with($stat['name'], 'media/')) {
            expect($stat['comp_method'])->toBe(ZipArchive::CM_STORE);
        }
    }
    $zip->close();
    expect($names)->toContain('zapvault-backup.json', 'db/zapvault.sqlite')
        ->and(array_values(array_filter($names, fn ($n) => preg_match('~^(media|thumbs)/~', $n))))->toEqualCanonicalizing(array_keys($before['files']));

    // Segundo backup no mesmo dia não sobrescreve o primeiro.
    $this->postJson('/api/backups')->assertStatus(202)->assertJsonPath('file_name', 'ZapVault-backup-2026-10-20-2.zip');
    Carbon::setTestNow();

    // Restaurar em uma pasta vazia.
    $target = useFileArchive(migrate: false);
    $this->artisan('zapvault:restore', ['arquivo' => $zipPath])
        ->expectsOutputToContain('Backup restaurado: 12 conversas')
        ->expectsOutputToContain('Nenhuma inconsistência encontrada')
        ->assertSuccessful();

    expect(archiveFingerprint($target))->toBe($before);
    $this->artisan('zapvault:verify', ['--hashes' => true])->assertSuccessful();
});

test('a restauração recusa sobrescrever uma pasta com dados', function () {
    $source = useFileArchive();
    seedFileDemo();
    $this->artisan('zapvault:backup')->expectsOutputToContain('Backup concluído')->assertSuccessful();
    $zipPath = File::glob("{$source}/backups/*.zip")[0];

    $before = archiveFingerprint($source);
    $this->artisan('zapvault:restore', ['arquivo' => $zipPath])
        ->expectsOutputToContain('só é feita em uma pasta vazia')
        ->assertFailed();
    expect(archiveFingerprint($source))->toBe($before);

    // Só mídia, sem banco, também conta como pasta com dados.
    $other = useFileArchive();
    File::ensureDirectoryExists("{$other}/media/ab/cd");
    File::put("{$other}/media/ab/cd/".str_repeat('a', 64).'.jpg', 'x');
    $this->artisan('zapvault:restore', ['arquivo' => $zipPath])->assertFailed();
});

test('a restauração recusa arquivos que não são backup do ZapVault', function () {
    $root = useFileArchive();

    $this->artisan('zapvault:restore', ['arquivo' => "{$root}/nao-existe.zip"])->expectsOutputToContain('não encontrado')->assertFailed();

    $other = makeZip(['_chat.txt' => "20/10/2026 18:04 - Ana: oi\n"]);
    $this->artisan('zapvault:restore', ['arquivo' => $other])->expectsOutputToContain('não é um backup do ZapVault')->assertFailed();

    $evil = makeZip(['zapvault-backup.json' => '{}', 'db/zapvault.sqlite' => '', '../fora.txt' => 'x']);
    $this->artisan('zapvault:restore', ['arquivo' => $evil])->expectsOutputToContain('arquivos inesperados')->assertFailed();
    expect(dirname($root).'/fora.txt')->not->toBeFile();
});

test('o backup deixa de fora rascunhos de importação e o próprio backup em andamento', function () {
    $source = useFileArchive();
    seedFileDemo();
    analyzeFiles([exportFixture('eduardo-am-pm.txt') => 'Conversa do WhatsApp com Eduardo Nunes.txt']);
    expect(DB::table('conversations')->where('status', 'draft')->count())->toBe(1);

    $this->artisan('zapvault:backup')->assertSuccessful();
    $zipPath = File::glob("{$source}/backups/*.zip")[0];

    useFileArchive(migrate: false);
    $this->artisan('zapvault:restore', ['arquivo' => $zipPath])->assertSuccessful();

    expect(DB::table('conversations')->where('status', 'draft')->count())->toBe(0)
        ->and(DB::table('import_batches')->where('status', 'open')->count())->toBe(0)
        ->and(DB::table('backups')->whereIn('status', ['waiting', 'running'])->count())->toBe(0)
        ->and(DB::table('conversations')->count())->toBe(12);
});

test('não começa um segundo backup enquanto um está em andamento', function () {
    useFileArchive();
    $running = Backup::query()->create(['status' => Backup::RUNNING, 'percent' => 40, 'started_at' => now()]);

    $this->postJson('/api/backups')->assertStatus(202)->assertJsonPath('id', $running->id)->assertJsonPath('percent', 40);
    $this->getJson('/api/settings')->assertJsonPath('current_backup.id', $running->id);
    $this->artisan('zapvault:backup')->expectsOutputToContain('Já há um backup em andamento')->assertFailed();

    // Um backup interrompido há muito tempo não trava os próximos.
    $running->update(['started_at' => now()->subDay()]);
    $this->postJson('/api/backups')->assertStatus(202)->assertJsonPath('status', 'done');
});

test('zapvault:migrate copia o banco antes de rodar migrations pendentes', function () {
    $root = useFileArchive();
    $this->artisan('zapvault:migrate')->expectsOutputToContain('já está atualizado')->assertSuccessful();
    expect(File::glob("{$root}/backups/*.sqlite"))->toBe([]);

    DB::table('migrations')->where('migration', '2026_10_20_000011_add_participant_index_to_messages')->delete();
    DB::statement('DROP INDEX IF EXISTS messages_participant_id_index');

    $this->artisan('zapvault:migrate')->expectsOutputToContain('Cópia do banco guardada')->assertSuccessful();
    expect(File::glob("{$root}/backups/antes-de-atualizar-*.sqlite"))->toHaveCount(1)
        ->and(DB::table('migrations')->where('migration', '2026_10_20_000011_add_participant_index_to_messages')->exists())->toBeTrue();
});

test('zapvault:migrate numa instalação nova só cria as tabelas', function () {
    useFileArchive(migrate: false);

    $this->artisan('zapvault:migrate')->assertSuccessful();
    expect(Schema::hasTable('messages_fts'))->toBeTrue();
});
