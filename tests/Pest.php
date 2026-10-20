<?php

use App\Import\ImportAnalyzer;
use App\Import\ImportService;
use App\Models\Conversation;
use App\Models\ImportBatch;
use App\Models\Message;
use App\Support\DataPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Funções auxiliares (somente dados fictícios)
|--------------------------------------------------------------------------
*/

function exportFixture(string $name): string
{
    return __DIR__.'/Fixtures/exports/'.$name;
}

/**
 * Usa uma pasta de dados temporária e vazia, apagada no fim do teste.
 */
function useTempDataPath(): string
{
    $root = storage_path('framework/testing/data-'.uniqid());
    config(['zapvault.data_path' => $root]);
    DataPath::ensure($root);

    test()->beforeApplicationDestroyed(fn () => File::deleteDirectory($root));

    return $root;
}

/**
 * Cria um .zip temporário. $entries: nome da entrada => conteúdo.
 *
 * @param  array<string, string>  $entries
 */
function makeZip(array $entries, ?string $name = null): string
{
    $dir = storage_path('framework/testing/zips-'.uniqid());
    File::ensureDirectoryExists($dir);
    test()->beforeApplicationDestroyed(fn () => File::deleteDirectory($dir));

    $path = $dir.'/'.($name ?? 'pacote.zip');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $entry => $content) {
        $zip->addFromString($entry, $content);
    }
    $zip->close();

    return $path;
}

/** Imagem JPEG fictícia gerada na hora. */
function fakeJpeg(int $width = 800, int $height = 600, int $seed = 0): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 40 + $seed % 200, 120, 200));
    ob_start();
    imagejpeg($image, null, 80);

    return (string) ob_get_clean();
}

/** Imagem WebP fictícia (figurinha). */
function fakeWebp(): string
{
    $image = imagecreatetruecolor(64, 64);
    imagefill($image, 0, 0, imagecolorallocate($image, 250, 200, 0));
    ob_start();
    imagewebp($image);

    return (string) ob_get_clean();
}

/**
 * Cria um lote, adiciona os arquivos e analisa todos.
 *
 * @param  array<int|string, string>  $files  caminho, ou caminho => nome original
 */
function analyzeFiles(array $files): ImportBatch
{
    $service = app(ImportService::class);
    $batch = $service->openOrCreateBatch();

    foreach ($files as $key => $value) {
        [$path, $name] = is_int($key) ? [$value, null] : [$key, $value];
        $service->addFile($batch, $path, $name);
    }

    foreach ($batch->files()->orderBy('id')->get() as $file) {
        (new ImportAnalyzer)->analyze($file);
    }

    return $batch->refresh();
}

/**
 * Analisa e conclui, somando às conversas existentes.
 *
 * @param  array<int|string, string>  $files
 * @return array{new: int, updated: int}
 */
function importFiles(array $files, string $choice = 'merge', ?string $me = null): array
{
    $batch = analyzeFiles($files);

    foreach ($batch->files as $file) {
        if (app(ImportService::class)->needsChoice($file)) {
            $file->update(['choice' => $choice]);
        }
    }

    return app(ImportService::class)->commit($batch, $me);
}

/**
 * Arquivos dentro da pasta de dados (para conferir que nada sobrou).
 *
 * @return list<string>
 */
function filesUnder(string $dir): array
{
    return is_dir($dir) ? array_map(fn ($f) => $f->getPathname(), File::allFiles($dir)) : [];
}

/**
 * Pasta de dados temporária com as 12 conversas fictícias do protótipo, com "hoje" em 20/10/2026.
 */
function seedDemo(): string
{
    $root = useTempDataPath();
    Carbon::setTestNow('2026-10-20 12:00:00');
    test()->artisan('zapvault:demo')->assertSuccessful();
    Carbon::setTestNow();

    return $root;
}

function conversationId(string $name): int
{
    return (int) Conversation::query()->where('name', $name)->value('id');
}

function messageId(string $conversation, string $body): int
{
    return (int) Message::query()->where('conversation_id', conversationId($conversation))->where('body', $body)->value('id');
}
