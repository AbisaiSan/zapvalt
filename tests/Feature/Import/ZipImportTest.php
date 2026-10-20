<?php

use App\Import\ImportException;
use App\Import\ImportService;
use App\Import\Media\MediaStore;
use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\ImportFile;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->root = useTempDataPath();
    $this->chat = file_get_contents(exportFixture('Conversa do WhatsApp com Maria Souza.txt'));
});

function mariaZip(string $chat, array $extra = []): string
{
    return makeZip(array_merge([
        'Conversa do WhatsApp com Maria Souza.txt' => $chat,
        'IMG-20261019-WA0007.jpg' => fakeJpeg(1600, 1200),
        'PTT-20261019-WA0009.opus' => 'audio fictício',
        'STK-20261019-WA0003.webp' => fakeWebp(),
        'Proposta_v3.pdf' => "%PDF-1.4\n% documento fictício\n",
        'nao-citado.jpg' => fakeJpeg(10, 10, 99),
    ], $extra), 'Conversa do WhatsApp com Maria Souza.zip');
}

test('importa .zip com mídia: anexos encontrados, faltantes e arquivos pelo hash', function () {
    $batch = analyzeFiles([mariaZip($this->chat)]);
    $file = $batch->files->first();

    expect($file->status)->toBe(ImportFile::DONE)
        ->and($file->stats['media_found'])->toBe(4)
        ->and($file->stats['media_missing'])->toBe(2);

    app(ImportService::class)->commit($batch);

    $image = Attachment::query()->where('original_name', 'IMG-20261019-WA0007.jpg')->sole();
    $sha = hash('sha256', file_get_contents($this->root.'/'.$image->storage_path));

    expect($image->sha256)->toBe($sha)
        ->and($image->storage_path)->toBe(MediaStore::shardedPath('media', $sha, 'jpg'))
        ->and([$image->width, $image->height])->toBe([1600, 1200])
        ->and($image->mime)->toBe('image/jpeg')
        ->and($image->thumb_path)->toBe(MediaStore::shardedPath('thumbs', $sha, 'jpg'))
        ->and(getimagesize($this->root.'/'.$image->thumb_path)[0])->toBe(640)
        ->and(Message::query()->where('body', 'STK-20261019-WA0003.webp')->value('kind'))->toBe('sticker')
        ->and(Message::query()->where('body', 'PTT-20261019-WA0009.opus')->value('kind'))->toBe('audio')
        ->and(Message::query()->where('body', 'Proposta_v3.pdf')->value('kind'))->toBe('doc')
        ->and(Message::query()->where('body', 'Proposta_v3.pdf')->value('search_text'))->toBe('proposta_v3.pdf')
        ->and(Message::query()->where('body', 'VID-20261020-WA0002.mp4')->value('kind'))->toBe('missing')
        ->and(count(filesUnder($this->root.'/media')))->toBe(4);
});

test('reimportar o .zip não regrava nem duplica mídia', function () {
    importFiles([mariaZip($this->chat)]);
    $files = filesUnder($this->root.'/media');

    importFiles([mariaZip($this->chat)]);

    expect(filesUnder($this->root.'/media'))->toBe($files)
        ->and(Attachment::query()->count())->toBe(4)
        ->and(Message::query()->count())->toBe(16);
});

test('cancelar apaga a mídia que nenhum outro anexo usa', function () {
    $batch = analyzeFiles([mariaZip($this->chat)]);

    expect(filesUnder($this->root.'/media'))->toHaveCount(4);

    app(ImportService::class)->cancel($batch);

    expect(filesUnder($this->root.'/media'))->toBe([])
        ->and(filesUnder($this->root.'/thumbs'))->toBe([])
        ->and(filesUnder($this->root.'/originals'))->toBe([])
        ->and(Attachment::query()->count())->toBe(0);
});

test('cancelar mantém a mídia usada por conversas já publicadas', function () {
    importFiles([mariaZip($this->chat)]);

    $batch = analyzeFiles([mariaZip($this->chat)]);
    app(ImportService::class)->cancel($batch);

    expect(filesUnder($this->root.'/media'))->toHaveCount(4)
        ->and(Attachment::query()->count())->toBe(4);
});

test('zip dentro de zip vira um cartão por conversa', function () {
    $inner1 = file_get_contents(makeZip(['_chat.txt' => file_get_contents(exportFixture('_chat.txt'))], 'WhatsApp Chat - Grupo Projeto Alfa.zip'));
    $inner2 = file_get_contents(mariaZip($this->chat));

    $outer = makeZip([
        'WhatsApp Chat - Grupo Projeto Alfa.zip' => $inner1,
        'Conversa do WhatsApp com Maria Souza.zip' => $inner2,
    ], 'takeout.zip');

    $batch = analyzeFiles([$outer]);

    expect($batch->files)->toHaveCount(2)
        ->and($batch->files->pluck('status')->unique()->all())->toBe([ImportFile::DONE]);

    app(ImportService::class)->commit($batch);

    expect(Conversation::query()->active()->orderBy('name')->pluck('name')->all())->toBe(['Grupo Projeto Alfa', 'Maria Souza'])
        ->and(Attachment::query()->count())->toBe(4)
        ->and(filesUnder($this->root.'/uploads-tmp'))->toBe([]);
});

test('recusa caminho com ../ no .zip', function () {
    $batch = analyzeFiles([makeZip(['../fora.txt' => $this->chat], 'malicioso.zip')]);

    expect($batch->files->first()->status)->toBe(ImportFile::FAILED)
        ->and($batch->files->first()->error_message)->toContain('caminho de arquivo inválido');
});

test('recusa caminho absoluto no .zip', function () {
    $batch = analyzeFiles([makeZip(['/etc/conversa.txt' => $this->chat], 'malicioso.zip')]);

    expect($batch->files->first()->error_message)->toContain('caminho de arquivo inválido');
});

test('recusa razão de compressão suspeita', function () {
    $batch = analyzeFiles([mariaZip($this->chat, ['bomba.bin' => str_repeat("\0", 5 * 1024 * 1024)])]);

    expect($batch->files->first()->status)->toBe(ImportFile::FAILED)
        ->and($batch->files->first()->error_message)->toContain('taxa de compressão suspeita');
});

test('recusa .zip grande demais depois de descompactado', function () {
    config(['zapvault.import.max_uncompressed_bytes' => 1000]);

    $batch = analyzeFiles([mariaZip($this->chat)]);

    expect($batch->files->first()->error_message)->toContain('grande demais');
});

test('recusa .zip com arquivos demais', function () {
    config(['zapvault.import.max_entries' => 3]);

    $batch = analyzeFiles([mariaZip($this->chat)]);

    expect($batch->files->first()->error_message)->toContain('arquivos demais');
});

test('recusa .zip falso', function () {
    $fake = makeZip([], 'vazio.zip');
    file_put_contents($fake, 'isto não é um zip');

    app(ImportService::class)->addFile(app(ImportService::class)->openOrCreateBatch(), $fake);
})->throws(ImportException::class, 'não é um .zip de verdade');

test('.zip sem conversa reconhecida falha', function () {
    $batch = analyzeFiles([makeZip(['leia-me.txt' => "texto qualquer\nsem datas\n", 'foto.jpg' => fakeJpeg(10, 10)], 'fotos.zip')]);

    expect($batch->files->first()->status)->toBe(ImportFile::FAILED)
        ->and($batch->files->first()->error_message)->toBe('Não foi possível reconhecer este arquivo como conversa.');
});

test('conversa do iPhone no .zip usa o nome do pacote', function () {
    $zip = makeZip([
        '_chat.txt' => file_get_contents(exportFixture('_chat.txt')),
        '00000012-PHOTO-2026-10-18-10-07-30.jpg' => fakeJpeg(),
    ], 'WhatsApp Chat - Grupo Projeto Alfa.zip');

    importFiles([$zip]);

    expect(Conversation::query()->active()->sole()->name)->toBe('Grupo Projeto Alfa')
        ->and(Message::query()->where('body', '00000012-PHOTO-2026-10-18-10-07-30.jpg')->value('kind'))->toBe('image');
});
