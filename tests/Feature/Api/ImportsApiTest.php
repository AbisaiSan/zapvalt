<?php

use App\Jobs\AnalyzeImportFile;
use App\Models\Conversation;
use App\Models\ImportBatch;
use App\Models\ImportFile;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->root = useTempDataPath();
    config(['zapvault.import.chunk_size' => 400]);
});

/** Envia um arquivo pela API em pedaços, como o navegador faz. */
function uploadFile($test, string $path, ?string $name = null): array
{
    $content = file_get_contents($path);
    $start = $test->postJson('/api/imports/uploads', ['name' => $name ?? basename($path), 'size' => strlen($content)])->assertCreated();
    $id = $start->json('upload_id');
    $chunk = $start->json('chunk_size');

    // Fora de ordem, para provar que cada pedaço vai para a sua posição.
    $pieces = str_split($content, $chunk);
    foreach (array_reverse(array_keys($pieces)) as $index) {
        $test->call('PUT', "/api/imports/uploads/{$id}?index={$index}", [], [], [], ['CONTENT_TYPE' => 'application/octet-stream'], $pieces[$index])
            ->assertNoContent();
    }

    return $test->postJson("/api/imports/uploads/{$id}/complete")->assertCreated()->json();
}

test('envia em pedaços, enfileira a análise e mostra o lote', function () {
    $file = uploadFile($this, exportFixture('Conversa do WhatsApp com Maria Souza.txt'));

    expect($file['status'])->toBe('done')
        ->and($file['stats']['new'])->toBe(16)
        ->and($file['conversation_name'])->toBe('Maria Souza');

    $stored = ImportFile::query()->find($file['id']);
    expect(file_get_contents($this->root.'/'.$stored->stored_path))->toBe(file_get_contents(exportFixture('Conversa do WhatsApp com Maria Souza.txt')))
        ->and($stored->sha256)->toBe(hash_file('sha256', exportFixture('Conversa do WhatsApp com Maria Souza.txt')));

    $this->getJson('/api/imports')
        ->assertOk()
        ->assertJsonPath('can_commit', true)
        ->assertJsonPath('hint', 'Tudo pronto para concluir')
        ->assertJsonPath('files.0.name', 'Conversa do WhatsApp com Maria Souza.txt')
        ->assertJsonPath('name_question.ask', true)
        ->assertJsonPath('name_question.candidates.0', ['name' => 'Maria Souza', 'hint' => 'Autor de 10 mensagens em 1 arquivo', 'suggested' => false]);
});

test('a análise vai para a fila', function () {
    Queue::fake();

    $file = uploadFile($this, exportFixture('_chat.txt'));

    expect($file['status'])->toBe('waiting');
    Queue::assertPushed(AnalyzeImportFile::class, fn ($job) => $job->importFileId === $file['id']);

    $this->getJson('/api/imports')
        ->assertJsonPath('can_commit', false)
        ->assertJsonPath('hint', 'Aguarde o processamento dos arquivos')
        ->assertJsonPath('name_question', null);
});

test('recusa extensão errada, zip falso e envio incompleto', function () {
    $this->postJson('/api/imports/uploads', ['name' => 'foto.jpg', 'size' => 10])
        ->assertUnprocessable()->assertJsonPath('message', 'Só é possível importar arquivos .txt ou .zip.');

    $start = $this->postJson('/api/imports/uploads', ['name' => 'falso.zip', 'size' => 5])->json();
    $this->call('PUT', "/api/imports/uploads/{$start['upload_id']}?index=0", [], [], [], [], 'abcde')->assertNoContent();
    $this->postJson("/api/imports/uploads/{$start['upload_id']}/complete")
        ->assertUnprocessable()->assertJsonPath('message', 'O arquivo .zip está corrompido ou não é um .zip de verdade.');

    $start = $this->postJson('/api/imports/uploads', ['name' => 'curto.txt', 'size' => 100])->json();
    $this->postJson("/api/imports/uploads/{$start['upload_id']}/complete")
        ->assertUnprocessable()->assertJsonPath('message', 'O envio ficou incompleto. Tente de novo.');
});

test('recusa pedaço maior que o arquivo', function () {
    $start = $this->postJson('/api/imports/uploads', ['name' => 'a.txt', 'size' => 10])->json();

    $this->call('PUT', "/api/imports/uploads/{$start['upload_id']}?index=0", [], [], [], [], str_repeat('x', 50))
        ->assertUnprocessable();
    $this->call('PUT', "/api/imports/uploads/{$start['upload_id']}?index=3", [], [], [], [], 'x')
        ->assertUnprocessable();
});

test('pede a escolha quando já existe a conversa e conclui somando', function () {
    importFiles([exportFixture('Conversa do WhatsApp com Maria Souza.txt')]);
    $existing = conversationId('Maria Souza');

    $file = uploadFile($this, exportFixture('Conversa do WhatsApp com Maria Souza - completa.txt'), 'Conversa do WhatsApp com Maria Souza.txt');

    expect($file['existing'])->toBe(['id' => $existing, 'name' => 'Maria Souza'])
        ->and($file['needs_choice'])->toBeTrue()
        ->and($file['stats'])->toMatchArray(['new' => 2, 'repeated' => 16]);

    $this->getJson('/api/imports')->assertJsonPath('can_commit', false)->assertJsonPath('hint', 'Escolha o que fazer com “Maria Souza”');
    $this->postJson('/api/imports/commit')->assertUnprocessable();

    $this->patchJson("/api/imports/files/{$file['id']}", ['choice' => 'merge'])->assertOk()->assertJsonPath('choice', 'merge');
    $this->patchJson("/api/imports/files/{$file['id']}", ['choice' => 'outra'])->assertUnprocessable();

    $this->postJson('/api/imports/commit', ['name_choice' => 'Rafael Moura'])->assertOk()->assertExactJson(['new' => 0, 'updated' => 1]);

    expect(Message::query()->count())->toBe(18)
        ->and(ImportBatch::query()->where('status', 'open')->count())->toBe(0);

    $this->getJson('/api/imports')->assertJsonPath('batch_id', null)->assertJsonPath('files', []);
});

test('cancelar pela API não deixa nada para trás', function () {
    uploadFile($this, exportFixture('_chat.txt'));
    $partial = $this->postJson('/api/imports/uploads', ['name' => 'metade.txt', 'size' => 1000])->json();
    $partialPath = $this->root.'/'.ImportFile::query()->find($partial['upload_id'])->stored_path;

    $this->postJson('/api/imports/cancel')->assertNoContent();

    expect(Conversation::query()->count())->toBe(0)
        ->and(Message::query()->count())->toBe(0)
        ->and(ImportFile::query()->count())->toBe(0)
        ->and(file_exists($partialPath))->toBeFalse()
        ->and(filesUnder($this->root.'/originals'))->toBe([]);
});

test('arquivo não reconhecido aparece como falha com a mensagem', function () {
    $file = uploadFile($this, exportFixture('nao-e-conversa.txt'));

    expect($file['status'])->toBe('failed')
        ->and($file['error_message'])->toBe('Não foi possível reconhecer este arquivo como conversa.');

    $this->getJson('/api/imports')->assertJsonPath('can_commit', false)->assertJsonPath('hint', 'Nenhum arquivo pôde ser importado');
});

test('linhas não reconhecidas aparecem no cartão', function () {
    $file = uploadFile($this, exportFixture('linhas-invalidas.txt'), 'Conversa do WhatsApp com Paula Alves.txt');

    expect($file['status'])->toBe('partial')
        ->and($file['unrecognized_count'])->toBe(2)
        ->and($file['unrecognized_lines'][0])->toBe(['line' => 2, 'text' => '13/10/2026 17:3 - Paula Alves: linha quebrada']);
});

test('não aceita pedaços para arquivo de lote já concluído', function () {
    $file = uploadFile($this, exportFixture('_chat.txt'));
    $this->postJson('/api/imports/commit')->assertOk();

    $this->call('PUT', "/api/imports/uploads/{$file['id']}?index=0", [], [], [], [], 'x')->assertNotFound();
});
