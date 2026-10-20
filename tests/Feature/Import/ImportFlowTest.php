<?php

use App\Import\ImportAnalyzer;
use App\Import\ImportException;
use App\Import\ImportService;
use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\ImportBatch;
use App\Models\ImportFile;
use App\Models\Message;
use App\Models\Participant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->root = useTempDataPath();
});

test('analisa um .txt como rascunho, com as contagens do cartão', function () {
    $batch = analyzeFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);
    $file = $batch->files->first();

    expect($file->status)->toBe(ImportFile::DONE)
        ->and($file->percent)->toBe(100)
        ->and($file->detected_format)->toBe('android_24h')
        ->and($file->stats)->toBe(['new' => 16, 'repeated' => 0, 'media_found' => 0, 'media_missing' => 6, 'lines_read' => 20])
        ->and($file->existing_conversation_id)->toBeNull();

    $draft = Conversation::query()->find($file->conversation_id);

    expect($draft->status)->toBe(Conversation::DRAFT)
        ->and($draft->name)->toBe('Maria Souza')
        ->and(Conversation::query()->active()->count())->toBe(0)
        ->and(is_file($this->root.'/'.$file->stored_path))->toBeTrue();
});

test('concluir publica a conversa com nome, datas e contagens', function () {
    $result = importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);

    $conversation = Conversation::query()->active()->sole();
    $last = Message::query()->find($conversation->last_message_id);

    expect($result)->toBe(['new' => 1, 'updated' => 0])
        ->and($conversation->name)->toBe('Maria Souza')
        ->and($conversation->is_group)->toBeFalse()
        ->and($conversation->source_file_name)->toBe('Conversa do WhatsApp com Maria Souza.txt')
        ->and($conversation->message_count)->toBe(14)
        ->and($conversation->first_message_at->format('Y-m-d H:i'))->toBe('2026-10-19 09:00')
        ->and($conversation->last_message_at->format('Y-m-d H:i'))->toBe('2026-10-20 09:22')
        ->and($last->body)->toBe('VID-20261020-WA0002.mp4')
        ->and(Participant::query()->orderBy('name')->pluck('message_count', 'name')->all())->toBe(['Maria Souza' => 10, 'Rafael Moura' => 4])
        ->and(ImportBatch::query()->sole()->status)->toBe(ImportBatch::COMMITTED);
});

test('grava a data e a hora exatamente como no arquivo, sem fuso', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);

    expect(DB::table('messages')->orderBy('id')->skip(1)->value('sent_at'))->toBe('2026-10-19 09:14:00');
});

test('reimportar o mesmo arquivo resulta em 0 mensagens novas', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);
    $before = Message::query()->count();

    $batch = analyzeFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);
    $file = $batch->files->first();

    expect($file->stats['new'])->toBe(0)
        ->and($file->stats['repeated'])->toBe(16)
        ->and($file->existing_conversation_id)->not->toBeNull()
        ->and(app(ImportService::class)->needsChoice($file))->toBeTrue();

    $file->update(['choice' => ImportFile::MERGE]);
    $result = app(ImportService::class)->commit($batch->refresh());

    expect($result)->toBe(['new' => 0, 'updated' => 1])
        ->and(Message::query()->count())->toBe($before)
        ->and(Conversation::query()->count())->toBe(1);
});

test('duas mensagens idênticas no mesmo minuto continuam duas', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);

    $oks = Message::query()->where('body', 'ok')->pluck('dedupe_key');

    expect($oks)->toHaveCount(2)
        ->and(substr($oks[0], -2))->toBe(':0')
        ->and(substr($oks[1], -2))->toBe(':1');
});

test('importar um arquivo maior que contém um menor soma só o que falta', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);

    $batch = analyzeFiles([exportFixture('Conversa do WhatsApp com Maria Souza - completa.txt') => 'Conversa do WhatsApp com Maria Souza.txt']);

    expect($batch->files->first()->stats['new'])->toBe(2)
        ->and($batch->files->first()->stats['repeated'])->toBe(16);

    $batch->files->first()->update(['choice' => ImportFile::MERGE]);
    app(ImportService::class)->commit($batch->refresh());

    $conversation = Conversation::query()->active()->sole();

    expect(Message::query()->count())->toBe(18)
        ->and($conversation->message_count)->toBe(16)
        ->and($conversation->last_message_at->format('H:i'))->toBe('18:05');
});

test('manter separada cria "Nome (2)" com todas as mensagens', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')], ImportFile::SEPARATE);

    expect(Conversation::query()->active()->orderBy('id')->pluck('name')->all())->toBe(['Maria Souza', 'Maria Souza (2)'])
        ->and(Message::query()->count())->toBe(32);
});

test('cancelar apaga rascunhos, participantes, mensagens e originais sem sobrar nada', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);
    $snapshot = [Conversation::count(), Participant::count(), Message::count(), DB::table('messages_fts_docsize')->count()];

    $batch = analyzeFiles([
        exportFixture('eduardo-am-pm.txt') => 'WhatsApp Chat with Eduardo Nunes.txt',
        exportFixture('_chat.txt'),
    ]);

    expect(Conversation::query()->where('status', 'draft')->count())->toBe(2);

    app(ImportService::class)->cancel($batch);

    expect([Conversation::count(), Participant::count(), Message::count(), DB::table('messages_fts_docsize')->count()])->toBe($snapshot)
        ->and(ImportFile::query()->where('batch_id', $batch->id)->count())->toBe(0)
        ->and($batch->refresh()->status)->toBe(ImportBatch::CANCELLED)
        ->and(is_dir($this->root."/originals/{$batch->id}"))->toBeFalse();
});

test('arquivo que não é conversa falha com mensagem clara e não cria nada', function () {
    $batch = analyzeFiles([exportFixture('nao-e-conversa.txt')]);
    $file = $batch->files->first();

    expect($file->status)->toBe(ImportFile::FAILED)
        ->and($file->error_message)->toBe(ImportAnalyzer::NOT_RECOGNIZED)
        ->and(Conversation::count())->toBe(0)
        ->and(app(ImportService::class)->canCommit($batch))->toBeFalse();
});

test('arquivo com linhas inválidas fica parcial, com a lista de linhas', function () {
    $batch = analyzeFiles([exportFixture('linhas-invalidas.txt')]);
    $file = $batch->files->first();

    expect($file->status)->toBe(ImportFile::PARTIAL)
        ->and($file->unrecognized_count)->toBe(2)
        ->and(array_column($file->unrecognized_lines, 'line'))->toBe([2, 4])
        ->and($file->stats['new'])->toBe(5)
        ->and(app(ImportService::class)->canCommit($batch))->toBeTrue();
});

test('reconhece grupo, nomes com emoji e avisos do iPhone', function () {
    importFiles([exportFixture('_chat.txt') => 'WhatsApp Chat - Grupo Projeto Alfa.txt']);

    $conversation = Conversation::query()->active()->sole();

    expect($conversation->name)->toBe('Grupo Projeto Alfa')
        ->and($conversation->is_group)->toBeTrue()
        ->and(Participant::query()->pluck('name')->sort()->values()->all())->toBe(['Ana Ribeiro 🌻', 'Carlos Lima', 'Luciana Prado', 'Rafael Moura'])
        ->and(Message::query()->where('kind', 'system')->count())->toBe(5)
        ->and(Message::query()->where('kind', 'call')->count())->toBe(1);
});

test('nome da conversa vem do nome do arquivo e telefone é detectado', function () {
    importFiles([exportFixture('eduardo-am-pm.txt') => 'WhatsApp Chat with +55 11 98765-4321.txt']);

    $conversation = Conversation::query()->active()->sole();

    expect($conversation->name)->toBe('+55 11 98765-4321')
        ->and($conversation->phone)->toBe('+55 11 98765-4321');
});

test('legenda e nome de documento entram em search_text; avisos e mídia faltante não', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);

    $searchable = Message::query()->whereNotNull('search_text')->pluck('search_text', 'kind');

    expect(Message::query()->where('body', 'Proposta_v3.pdf')->value('search_text'))->toBeNull()
        ->and(Message::query()->where('kind', 'text')->whereNull('search_text')->count())->toBe(0)
        ->and(Message::query()->whereIn('kind', ['system', 'call', 'deleted', 'missing'])->whereNotNull('search_text')->count())->toBe(0)
        ->and($searchable->keys()->all())->toBe(['text']);
});

test('sugere o nome do usuário quando ele aparece na maioria dos arquivos', function () {
    $batch = analyzeFiles([
        exportFixture('Conversa do WhatsApp com Maria Souza.txt'),
        exportFixture('eduardo-am-pm.txt') => 'WhatsApp Chat with Eduardo Nunes.txt',
        exportFixture('linhas-invalidas.txt') => 'Conversa do WhatsApp com Paula Alves.txt',
    ]);

    $question = app(ImportService::class)->nameCandidates($batch);

    expect($question['ask'])->toBeTrue()
        ->and($question['candidates'][0])->toBe(['name' => 'Rafael Moura', 'files' => 3, 'messages' => 8, 'suggested' => true])
        ->and($question['candidates'][1]['suggested'])->toBeFalse();

    app(ImportService::class)->commit($batch, 'Rafael Moura');

    expect(Setting::userNames())->toBe(['Rafael Moura'])
        ->and(Participant::query()->where('is_me', true)->count())->toBe(3)
        ->and(Participant::query()->where('is_me', true)->distinct()->pluck('name')->all())->toBe(['Rafael Moura']);
});

test('sem sugestão quando o primeiro candidato não se destaca', function () {
    $batch = analyzeFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);

    $question = app(ImportService::class)->nameCandidates($batch);

    expect($question['ask'])->toBeTrue()
        ->and(collect($question['candidates'])->pluck('suggested')->unique()->all())->toBe([false]);
});

test('pula a pergunta do nome quando um nome configurado aparece como autor', function () {
    Setting::put('user_names', ['rafael moura']);

    $batch = analyzeFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);

    expect(app(ImportService::class)->nameCandidates($batch))->toBe(['ask' => false, 'candidates' => []])
        ->and(Participant::query()->where('is_me', true)->pluck('name')->all())->toBe(['Rafael Moura']);
});

test('dois arquivos da mesma conversa no mesmo lote viram uma conversa só', function () {
    $result = importFiles([
        exportFixture('Conversa do WhatsApp com Maria Souza.txt'),
        exportFixture('Conversa do WhatsApp com Maria Souza - completa.txt') => 'Conversa do WhatsApp com Maria Souza.txt',
    ]);

    expect($result)->toBe(['new' => 1, 'updated' => 1])
        ->and(Conversation::query()->active()->count())->toBe(1)
        ->and(Message::query()->count())->toBe(18);
});

test('recusa extensões que não são .txt ou .zip', function () {
    $batch = app(ImportService::class)->openOrCreateBatch();

    app(ImportService::class)->addFile($batch, exportFixture('_chat.txt'), 'conversa.pdf');
})->throws(ImportException::class, 'Só é possível importar arquivos .txt ou .zip.');

test('não grava texto de mensagem no log quando algo falha', function () {
    Log::spy();

    $batch = analyzeFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);
    // Força um erro inesperado: o original some antes de uma nova análise.
    $file = $batch->files->first();
    $file->update(['status' => ImportFile::WAITING]);
    unlink($this->root.'/'.$file->stored_path);
    file_put_contents($this->root.'/'.$file->stored_path, "\x00");
    chmod($this->root.'/'.$file->stored_path, 0000);

    (new ImportAnalyzer)->analyze($file->refresh());

    chmod($this->root.'/'.$file->stored_path, 0600);

    Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
        return ! str_contains(json_encode($context), 'proposta');
    });
    expect($file->refresh()->status)->toBe(ImportFile::FAILED);
});

test('o anexo ganha o arquivo quando a mesma conversa chega depois com mídia', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);

    $zip = makeZip([
        'Conversa do WhatsApp com Maria Souza.txt' => file_get_contents(exportFixture('Conversa do WhatsApp com Maria Souza.txt')),
        'IMG-20261019-WA0007.jpg' => fakeJpeg(),
    ], 'Conversa do WhatsApp com Maria Souza.zip');

    importFiles([$zip]);

    $message = Message::query()->where('body', 'IMG-20261019-WA0007.jpg')->sole();

    expect($message->kind)->toBe('image')
        ->and($message->caption)->toBe('Esse é o espaço')
        ->and($message->search_text)->toBe('esse e o espaco')
        ->and(Attachment::query()->where('message_id', $message->id)->exists())->toBeTrue()
        ->and(Message::query()->count())->toBe(16);
});
