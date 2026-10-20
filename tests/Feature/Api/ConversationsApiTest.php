<?php

use App\Api\Cursor;
use App\Api\Presenter;
use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->root = seedDemo();
});

test('lista as conversas pela última mensagem, no formato da especificação', function () {
    $response = $this->getJson('/api/conversations')->assertOk();

    expect($response->json('items.0'))->toBe([
        'id' => conversationId('Maria Souza'),
        'name' => 'Maria Souza',
        'is_group' => false,
        'is_phone' => false,
        'avatar_index' => 0,
        'preview' => 'Segue o PDF com os valores atualizados.',
        'last_message_at' => '2026-10-20T09:21:00',
    ])
        ->and(collect($response->json('items'))->pluck('name')->all())->toBe([
            'Maria Souza', 'Grupo Projeto Alfa', 'João Pereira', 'Gráfica Rápida', 'Grupo Financeiro', 'Fernanda Lima',
            'Transportadora Norte', 'Ricardo Alves', 'Clínica Vida', 'Grupo Parceiros', 'Paulo Mendes', '+55 98 91234-5678',
        ])
        ->and($response->json('next_cursor'))->toBeNull();
});

test('monta a prévia como no protótipo', function () {
    $previews = collect($this->getJson('/api/conversations')->json('items'))->pluck('preview', 'name');

    expect($previews['Grupo Projeto Alfa'])->toBe('Carlos: reunião confirmada para quinta')
        ->and($previews['Gráfica Rápida'])->toBe('Orçamento da proposta em anexo')
        ->and($previews['Grupo Financeiro'])->toBe('Ana: conciliação fechada')
        ->and($previews['Fernanda Lima'])->toBe('Pode me ligar amanhã cedo?')
        ->and($previews['Grupo Parceiros'])->toBe('Luciana: nova PROPOSTA enviada')
        ->and($previews['+55 98 91234-5678'])->toBe('Olá, vi o anúncio');
});

test('resume cada tipo de mensagem na prévia', function (string $kind, ?string $body, ?string $caption, ?int $duration, string $expected) {
    expect(Presenter::summary($kind, $body, $caption, $duration))->toBe($expected);
})->with([
    ['text', "linha 1\n\nlinha 2", null, null, 'linha 1 linha 2'],
    ['image', 'IMG.jpg', null, null, 'Foto'],
    ['image', 'IMG.jpg', 'Fachada', null, 'Foto: Fachada'],
    ['video', 'VID.mp4', null, null, 'Vídeo'],
    ['video', 'VID.mp4', 'Demo', null, 'Vídeo: Demo'],
    ['audio', 'PTT.opus', null, 42, 'Áudio (0:42)'],
    ['audio', 'PTT.opus', null, null, 'Áudio'],
    ['doc', 'Proposta_v3.pdf', null, null, 'Proposta_v3.pdf'],
    ['sticker', 'STK.webp', null, null, 'Figurinha'],
    ['missing', null, null, null, 'Mídia não incluída'],
    ['deleted', null, null, null, 'Mensagem apagada'],
]);

test('pagina a lista com cursor', function () {
    $first = $this->getJson('/api/conversations?limit=5')->assertOk();
    $second = $this->getJson('/api/conversations?limit=5&cursor='.$first->json('next_cursor'))->assertOk();
    $third = $this->getJson('/api/conversations?limit=5&cursor='.$second->json('next_cursor'))->assertOk();

    $names = collect([...$first->json('items'), ...$second->json('items'), ...$third->json('items')])->pluck('name');

    expect($names)->toHaveCount(12)
        ->and($names->unique())->toHaveCount(12)
        ->and($third->json('next_cursor'))->toBeNull();
});

test('rascunhos de importação não aparecem nem abrem', function () {
    $draft = Conversation::factory()->draft()->create(['last_message_at' => '2030-01-01 00:00:00']);

    expect(collect($this->getJson('/api/conversations')->json('items'))->pluck('id'))->not->toContain($draft->id);

    $this->getJson("/api/conversations/{$draft->id}")->assertNotFound();
    $this->getJson("/api/conversations/{$draft->id}/messages")->assertNotFound();
});

test('mostra o cabeçalho da conversa com participantes e cores', function () {
    $id = conversationId('Grupo Projeto Alfa');

    $response = $this->getJson("/api/conversations/{$id}")->assertOk();

    expect($response->json())->toMatchArray([
        'id' => $id,
        'name' => 'Grupo Projeto Alfa',
        'is_group' => true,
        'avatar_index' => 2,
        'first_message_at' => '2026-10-09T00:00:00',
        'last_message_at' => '2026-10-19T17:05:00',
        'message_count' => 25,
        'participant_count' => 4,
        'source_file_name' => 'Conversa com Grupo Projeto Alfa.zip',
    ])
        ->and(collect($response->json('participants'))->map(fn ($p) => [$p['name'], $p['is_me'], $p['color_slot']])->all())->toBe([
            ['Moura Consultoria', true, null],
            ['Carlos Lima', false, 2],
            ['Ana Ribeiro', false, 5],
            ['Luciana Prado', false, 0],
        ]);
});

test('renomeia a conversa', function () {
    $id = conversationId('Paulo Mendes');

    $this->patchJson("/api/conversations/{$id}", ['name' => '  Paulo Mendes (Indicação)  '])
        ->assertOk()
        ->assertJsonPath('name', 'Paulo Mendes (Indicação)')
        ->assertJsonPath('preview', 'Bom dia, tudo bem?');

    expect(Conversation::query()->find($id)->name_normalized)->toBe('paulo mendes (indicacao)');

    $this->patchJson("/api/conversations/{$id}", ['name' => ''])->assertUnprocessable();
});

test('exclui a conversa, os anexos e a mídia que ninguém mais usa', function () {
    $id = conversationId('Maria Souza');
    $files = Attachment::query()->whereHas('message', fn ($q) => $q->where('conversation_id', $id))->pluck('storage_path', 'sha256');

    $this->deleteJson("/api/conversations/{$id}")->assertNoContent();

    expect(Conversation::query()->find($id))->toBeNull()
        ->and(Message::query()->where('conversation_id', $id)->count())->toBe(0)
        ->and($files)->toHaveCount(7);

    // Arquivo idêntico usado por outra conversa fica; os demais são apagados.
    foreach ($files as $sha => $path) {
        $stillUsed = Attachment::query()->where('sha256', $sha)->exists();
        expect(file_exists($this->root.'/'.$path))->toBe($stillUsed);
    }

    $this->artisan('zapvault:verify')->assertSuccessful();
});

test('resume o acervo', function () {
    $this->getJson('/api/archive/summary')
        ->assertOk()
        ->assertJson([
            'conversations' => 12,
            'messages' => 97,
            'first_message_at' => '2026-09-02T11:00:00',
            'last_message_at' => '2026-10-20T09:21:00',
            'attachments' => 22,
        ])
        ->assertJsonStructure(['bytes_used']);

    expect($this->getJson('/api/archive/summary')->json('bytes_used'))->toBeGreaterThan(1_000_000);
});

test('cursor inválido é ignorado', function () {
    expect(Cursor::decode('lixo'))->toBeNull()
        ->and(Cursor::decode(Cursor::encode('2026-10-20 09:21:00', 7)))->toBe(['2026-10-20 09:21:00', 7]);

    $this->getJson('/api/conversations?cursor=lixo')->assertOk()->assertJsonCount(12, 'items');
});
