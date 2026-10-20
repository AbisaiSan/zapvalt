<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Search\MessageSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedDemo();
});

test('busca de mensagens no formato da especificação, da mais recente para a mais antiga', function () {
    $response = $this->getJson('/api/search/messages?q=proposta')->assertOk();

    expect($response->json('total'))->toBe(12)
        ->and($response->json('conversations'))->toBe(4)
        ->and($response->json('total_capped'))->toBeFalse()
        ->and($response->json('results.0'))->toBe([
            'message_id' => messageId('Maria Souza', 'Proposta_v3.pdf'),
            'conversation_id' => conversationId('Maria Souza'),
            'conversation_name' => 'Maria Souza',
            'sent_at' => '2026-10-20T09:21:00',
            'who' => 'Você',
            'field' => 'file',
            'kind' => 'doc',
            'text' => 'Proposta_v3.pdf',
        ]);

    $dates = collect($response->json('results'))->pluck('sent_at');
    expect($dates->all())->toBe($dates->sortDesc()->values()->all());
});

test('mesmos resultados com qualquer caixa e com ou sem acento', function (string $a, string $b) {
    $ids = fn ($q) => collect($this->getJson('/api/search/messages?q='.urlencode($q))->json('results'))->pluck('message_id')->all();

    expect($ids($a))->toBe($ids($b))->not->toBeEmpty();
})->with([
    ['PROPOSTA', 'proposta'],
    ['Proposta', 'proposta'],
    ['acao', 'ação'],
    ['reuniao', 'REUNIÃO'],
    ['conciliacao', 'conciliação'],
]);

test('quem enviou: "Você", primeiro nome em grupos e nulo em conversa individual', function () {
    $results = collect($this->getJson('/api/search/messages?q=proposta')->json('results'));

    expect($results->firstWhere('text', 'nova PROPOSTA enviada')['who'])->toBe('Luciana')
        ->and($results->firstWhere('text', 'PROPOSTA aprovada pelo financeiro!')['who'])->toBeNull()
        ->and($results->firstWhere('text', 'Proposta enviada por e-mail. Qualquer dúvida, me chama.')['who'])->toBe('Você');
});

test('legenda de imagem e nome de documento são pesquisáveis', function () {
    $results = collect($this->getJson('/api/search/messages?q=extrato do dia')->json('results'));

    expect($results->pluck('field')->unique()->sort()->values()->all())->toBe(['caption', 'text'])
        ->and($results->firstWhere('field', 'caption')['kind'])->toBe('image');

    expect($this->getJson('/api/search/messages?q=cronograma_alfa')->json('results.0.field'))->toBe('file');
});

test('avisos, apagadas, áudios, figurinhas e mídia faltante não aparecem', function () {
    expect($this->getJson('/api/search/messages?q=criptografia')->json('total'))->toBe(0)
        ->and($this->getJson('/api/search/messages?q=chamada')->json('total'))->toBe(0)
        ->and($this->getJson('/api/search/messages?q=STK-2026')->json('total'))->toBe(0)
        ->and($this->getJson('/api/search/messages?q=PTT-2026')->json('total'))->toBe(0)
        ->and($this->getJson('/api/search/messages?q=adicionou')->json('total'))->toBe(0);
});

test('2 caracteres usam LIKE, 3 ou mais usam FTS e 1 não busca', function () {
    DB::enableQueryLog();
    $this->getJson('/api/search/messages?q=ok')->assertOk();
    $two = collect(DB::getQueryLog())->pluck('query')->implode(' ');

    DB::flushQueryLog();
    $this->getJson('/api/search/messages?q=bom')->assertOk();
    $three = collect(DB::getQueryLog())->pluck('query')->implode(' ');

    expect($two)->toContain('LIKE')->not->toContain('MATCH')
        ->and($three)->toContain('MATCH')
        ->and($this->getJson('/api/search/messages?q=o')->json())->toBe(['results' => [], 'total' => 0, 'total_capped' => false, 'conversations' => 0, 'next_cursor' => null]);
});

test('ç, ñ, ü e emojis funcionam', function () {
    $conversation = Conversation::query()->where('name', 'Paulo Mendes')->sole();
    Message::factory()->for($conversation)->text('Señor Müller, coração 🎉 festa')->create(['sent_at' => '2026-09-16 10:00:00']);

    foreach (['senor', 'MULLER', 'coracao', '🎉 festa', 'CORAÇÃO'] as $q) {
        expect($this->getJson('/api/search/messages?q='.urlencode($q))->json('total'))->toBe(1, $q);
    }
});

test('termo com % e _ é literal no LIKE', function () {
    expect($this->getJson('/api/search/messages?q='.urlencode('%_'))->json('total'))->toBe(0);
});

test('pagina os resultados com cursor', function () {
    $first = $this->getJson('/api/search/messages?q=proposta&limit=5');
    $second = $this->getJson('/api/search/messages?q=proposta&limit=5&cursor='.$first->json('next_cursor'));
    $third = $this->getJson('/api/search/messages?q=proposta&limit=5&cursor='.$second->json('next_cursor'));

    $all = collect([...$first->json('results'), ...$second->json('results'), ...$third->json('results')])->pluck('message_id');

    expect($all)->toHaveCount(12)->and($all->unique())->toHaveCount(12)
        ->and($third->json('next_cursor'))->toBeNull()
        ->and($second->json())->not->toHaveKey('total');
});

test('a contagem para em 10.000', function () {
    $conversation = Conversation::factory()->create();
    $rows = [];
    for ($i = 0; $i < 10_005; $i++) {
        $rows[] = ['conversation_id' => $conversation->id, 'sent_at' => '2025-01-01 10:00:00', 'kind' => 'text',
            'body' => 'teste repetido', 'search_text' => 'teste repetido', 'dedupe_key' => "k{$i}"];
    }
    foreach (array_chunk($rows, 1000) as $chunk) {
        DB::table('messages')->insert($chunk);
    }

    $response = $this->getJson('/api/search/messages?q=repetido');

    expect($response->json('total'))->toBe(MessageSearch::COUNT_CAP)
        ->and($response->json('total_capped'))->toBeTrue()
        ->and($response->json('results'))->toHaveCount(50);
});

test('busca de conversas pelo nome, ignorando acentos', function () {
    $response = $this->getJson('/api/search/conversations?q=grafica')->assertOk();

    expect($response->json('total'))->toBe(1)
        ->and($response->json('items.0.name'))->toBe('Gráfica Rápida')
        ->and($response->json('items.0'))->toHaveKeys(['id', 'name', 'is_group', 'is_phone', 'avatar_index', 'preview', 'last_message_at'])
        ->and(collect($this->getJson('/api/search/conversations?q=GRUPO')->json('items'))->pluck('name')->all())
        ->toBe(['Grupo Projeto Alfa', 'Grupo Financeiro', 'Grupo Parceiros']);
});

test('rascunhos ficam fora da busca', function () {
    $draft = Conversation::factory()->draft()->create(['name' => 'Rascunho Secreto', 'name_normalized' => 'rascunho secreto']);
    Message::factory()->for($draft)->text('palavrasecreta')->create();

    expect($this->getJson('/api/search/conversations?q=secreto')->json('total'))->toBe(0)
        ->and($this->getJson('/api/search/messages?q=palavrasecreta')->json('total'))->toBe(0);
});
