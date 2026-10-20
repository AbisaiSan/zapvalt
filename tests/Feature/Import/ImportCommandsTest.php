<?php

use App\Models\Conversation;
use App\Models\ImportBatch;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->root = useTempDataPath();
});

test('zapvault:import --commit importa e reimportar não cria mensagens novas', function () {
    $this->artisan('zapvault:import', ['arquivos' => [exportFixture('Conversa do WhatsApp com Maria Souza.txt')], '--commit' => true])
        ->expectsOutputToContain('1 conversa(s) nova(s), 0 atualizada(s)')
        ->assertSuccessful();

    $count = Message::query()->count();

    $this->artisan('zapvault:import', ['arquivos' => [exportFixture('Conversa do WhatsApp com Maria Souza.txt')], '--commit' => true])
        ->expectsOutputToContain('0 conversa(s) nova(s), 1 atualizada(s)')
        ->assertSuccessful();

    expect(Message::query()->count())->toBe($count)
        ->and(Conversation::query()->count())->toBe(1);
});

test('zapvault:import sem --commit, fora do modo interativo, não grava nada', function () {
    $this->artisan('zapvault:import', ['arquivos' => [exportFixture('_chat.txt')], '--no-interaction' => true])
        ->expectsOutputToContain('Nada foi gravado no acervo')
        ->assertSuccessful();

    expect(Conversation::query()->count())->toBe(0)
        ->and(Message::query()->count())->toBe(0);
});

test('zapvault:import interativo pergunta e conclui', function () {
    $this->artisan('zapvault:import', ['arquivos' => [exportFixture('Conversa do WhatsApp com Maria Souza.txt')]])
        ->expectsChoice('Este é o seu nome nas mensagens?', 'none', [
            'c0' => 'Maria Souza (autor de 10 mensagens em 1 arquivos)',
            'c1' => 'Rafael Moura (autor de 4 mensagens em 1 arquivos)',
            'none' => 'Nenhum destes',
        ])
        ->expectsConfirmation('Concluir a importação?', 'yes')
        ->assertSuccessful();

    expect(Conversation::query()->active()->count())->toBe(1);
});

test('zapvault:import --me define o nome do usuário', function () {
    $this->artisan('zapvault:import', ['arquivos' => [exportFixture('Conversa do WhatsApp com Maria Souza.txt')], '--commit' => true, '--me' => 'Rafael Moura'])
        ->assertSuccessful();

    expect(DB::table('participants')->where('is_me', true)->pluck('name')->all())->toBe(['Rafael Moura']);
});

test('zapvault:import falha quando nenhum arquivo é conversa', function () {
    $this->artisan('zapvault:import', ['arquivos' => [exportFixture('nao-e-conversa.txt')], '--commit' => true])
        ->expectsOutputToContain('Nada foi importado')
        ->assertFailed();

    expect(ImportBatch::query()->where('status', 'open')->count())->toBe(0);
});

test('zapvault:import recusa quando há importação em andamento na tela', function () {
    analyzeFiles([exportFixture('_chat.txt')]);

    $this->artisan('zapvault:import', ['arquivos' => [exportFixture('Conversa do WhatsApp com Maria Souza.txt')], '--commit' => true])
        ->expectsOutputToContain('importação em andamento')
        ->assertFailed();
});

test('zapvault:verify não aponta inconsistências depois de importar', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt'), exportFixture('_chat.txt')]);

    $this->artisan('zapvault:verify', ['--hashes' => true])
        ->expectsOutputToContain('Nenhuma inconsistência encontrada')
        ->assertSuccessful();
});

test('zapvault:verify aponta contagens erradas, índice incompleto e mídia ausente', function () {
    $zip = makeZip([
        'Conversa do WhatsApp com Maria Souza.txt' => file_get_contents(exportFixture('Conversa do WhatsApp com Maria Souza.txt')),
        'IMG-20261019-WA0007.jpg' => fakeJpeg(),
    ], 'Conversa do WhatsApp com Maria Souza.zip');
    importFiles([$zip]);

    DB::table('conversations')->update(['message_count' => 999]);
    DB::statement("INSERT INTO messages_fts (messages_fts) VALUES ('delete-all')");
    $path = DB::table('attachments')->value('storage_path');
    unlink($this->root.'/'.$path);

    $this->artisan('zapvault:verify')
        ->expectsOutputToContain('contagem de mensagens 999')
        ->expectsOutputToContain('Rode php artisan zapvault:reindex')
        ->expectsOutputToContain('1 anexo(s) sem arquivo no disco')
        ->assertFailed();
});

test('zapvault:reindex reconstrói o índice de busca', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);
    DB::statement("INSERT INTO messages_fts (messages_fts) VALUES ('delete-all')");

    $this->artisan('zapvault:reindex')->assertSuccessful();

    $hits = DB::table('messages_fts')->whereRaw('messages_fts MATCH ?', ['"aprovada"'])->count();

    expect($hits)->toBe(1);
    $this->artisan('zapvault:verify')->assertSuccessful();
});
