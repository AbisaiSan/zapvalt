<?php

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedDemo();
    $this->maria = conversationId('Maria Souza');
});

function ids($response): array
{
    return collect($response->json('messages'))->pluck('id')->all();
}

test('sem parâmetros devolve as mais recentes em ordem cronológica', function () {
    $response = $this->getJson("/api/conversations/{$this->maria}/messages?limit=4")->assertOk();
    $all = Message::query()->where('conversation_id', $this->maria)->orderBy('sent_at')->orderBy('id')->pluck('id')->all();

    expect(ids($response))->toBe(array_slice($all, -4))
        ->and($response->json('has_older'))->toBeTrue()
        ->and($response->json('has_newer'))->toBeFalse()
        ->and($response->json())->not->toHaveKey('focus_id');
});

test('mensagem no formato da especificação', function () {
    $id = messageId('Maria Souza', 'IMG-20261019-WA0007.jpg');
    $message = collect($this->getJson("/api/conversations/{$this->maria}/messages?around={$id}&limit=3")->json('messages'))->firstWhere('id', $id);
    $attachment = Attachment::query()->where('message_id', $id)->sole();

    expect($message)->toBe([
        'id' => $id,
        'sent_at' => '2026-10-19T09:30:00',
        'kind' => 'image',
        'sender' => ['participant_id' => $message['sender']['participant_id'], 'name' => 'Maria Souza', 'is_me' => false, 'color_slot' => 0],
        'text' => null,
        'caption' => 'Esse é o espaço',
        'system_icon' => null,
        'attachment' => [
            'id' => $attachment->id, 'name' => 'IMG-20261019-WA0007.jpg', 'ext' => 'jpg',
            'size_bytes' => $attachment->size_bytes, 'width' => 1200, 'height' => 900,
            'duration' => null, 'pages' => null,
            'url' => "/media/{$attachment->id}", 'thumb_url' => "/media/{$attachment->id}/thumb",
        ],
    ]);
});

test('avisos e chamadas vêm sem remetente e com o texto', function () {
    $messages = collect($this->getJson("/api/conversations/{$this->maria}/messages")->json('messages'));

    expect($messages->firstWhere('kind', 'system'))->toMatchArray([
        'sender' => null,
        'system_icon' => 'lock',
        'text' => 'As mensagens e ligações são protegidas com a criptografia de ponta a ponta.',
    ])
        ->and($messages->firstWhere('kind', 'call'))->toMatchArray(['sender' => null, 'text' => 'Chamada de voz perdida'])
        ->and($messages->firstWhere('kind', 'deleted')['text'])->toBeNull()
        ->and($messages->firstWhere('kind', 'missing')['attachment'])->toBeNull();
});

test('before e after percorrem a conversa inteira sem repetir', function () {
    $all = Message::query()->where('conversation_id', $this->maria)->orderBy('sent_at')->orderBy('id')->pluck('id')->all();

    $page = $this->getJson("/api/conversations/{$this->maria}/messages?limit=7");
    $seen = ids($page);
    while ($page->json('has_older')) {
        $page = $this->getJson("/api/conversations/{$this->maria}/messages?limit=7&before=".$page->json('older_cursor'));
        $seen = [...ids($page), ...$seen];
    }

    expect($seen)->toBe($all);

    $page = $this->getJson("/api/conversations/{$this->maria}/messages?limit=7&day=2026-10-19");
    $forward = ids($page);
    while ($page->json('has_newer')) {
        $page = $this->getJson("/api/conversations/{$this->maria}/messages?limit=7&after=".$page->json('newer_cursor'));
        $forward = [...$forward, ...ids($page)];
    }

    expect($forward)->toBe($all);
});

test('around traz mensagens antes e depois, incluindo a própria, com focus_id', function () {
    $all = Message::query()->where('conversation_id', $this->maria)->orderBy('sent_at')->orderBy('id')->pluck('id')->all();
    $target = $all[15];

    $response = $this->getJson("/api/conversations/{$this->maria}/messages?around={$target}&limit=6")->assertOk();

    expect($response->json('focus_id'))->toBe($target)
        ->and(ids($response))->toBe(array_slice($all, 12, 6))
        ->and($response->json('has_older'))->toBeTrue()
        ->and($response->json('has_newer'))->toBeTrue();
});

test('around com mensagem de outra conversa dá 404', function () {
    $other = messageId('João Pereira', 'Perfeito, obrigado!');

    $this->getJson("/api/conversations/{$this->maria}/messages?around={$other}")->assertNotFound();
});

test('day começa na primeira mensagem do dia', function () {
    $response = $this->getJson("/api/conversations/{$this->maria}/messages?day=2026-10-20&limit=3")->assertOk();
    $first = $response->json('messages.0');

    expect($first['sent_at'])->toBe('2026-10-20T08:12:00')
        ->and($response->json('focus_id'))->toBe($first['id'])
        ->and($response->json('has_older'))->toBeTrue();

    $this->getJson("/api/conversations/{$this->maria}/messages?day=20-10-2026")->assertUnprocessable();
});

test('lista os dias com a quantidade de mensagens', function () {
    $this->getJson("/api/conversations/{$this->maria}/days")
        ->assertOk()
        ->assertExactJson(['days' => [['day' => '2026-10-19', 'count' => 18], ['day' => '2026-10-20', 'count' => 12]]]);
});

test('busca na conversa devolve mensagens em ordem e ocorrências sem sobreposição', function () {
    $response = $this->getJson("/api/conversations/{$this->maria}/matches?q=PROPOSTA")->assertOk();

    expect($response->json('total'))->toBe(5)
        ->and(collect($response->json('matches'))->pluck('sent_at')->all())->toBe(collect($response->json('matches'))->pluck('sent_at')->sort()->values()->all());

    $text = Message::factory()->for(Conversation::query()->find($this->maria))->text('aaaa e aaa')->create(['sent_at' => '2026-10-21 10:00:00']);

    $aa = $this->getJson("/api/conversations/{$this->maria}/matches?q=aa")->json('matches');

    expect(collect($aa)->firstWhere('message_id', $text->id)['count'])->toBe(3);
});

test('busca na conversa com 1 caractere não procura', function () {
    $this->getJson("/api/conversations/{$this->maria}/matches?q=a")->assertExactJson(['total' => 0, 'matches' => []]);
});

test('lista imagens e vídeos para o visualizador', function () {
    $items = $this->getJson("/api/conversations/{$this->maria}/media")->assertOk()->json('items');

    expect(collect($items)->pluck('name')->all())->toBe(['IMG-20261019-WA0007.jpg', 'IMG-20261019-WA0008.jpg', 'IMG-20261020-WA0001.jpg', 'VID-20261020-WA0002.mp4'])
        ->and($items[0])->toMatchArray(['kind' => 'image', 'caption' => 'Esse é o espaço', 'width' => 1200, 'height' => 900, 'sender' => ['name' => 'Maria Souza', 'is_me' => false]])
        ->and($items[3])->toMatchArray(['kind' => 'video', 'duration' => 18, 'caption' => 'Montagem do ano passado']);
});
