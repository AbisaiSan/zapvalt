<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Support\TextNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function ftsMatch(string $term): array
{
    $phrase = TextNormalizer::ftsPhrase(TextNormalizer::normalize($term));

    return DB::table('messages_fts')
        ->whereRaw('messages_fts MATCH ?', [$phrase])
        ->orderBy('rowid')
        ->pluck('rowid')
        ->all();
}

test('encontra trecho de palavra ignorando caixa e acentos', function () {
    $conversation = Conversation::factory()->create();
    $a = Message::factory()->for($conversation)->text('PROPOSTA aprovada pelo financeiro!')->create();
    $b = Message::factory()->for($conversation)->text('Ação de divulgação na fachada')->create();
    Message::factory()->for($conversation)->text('Bom dia')->create();

    expect(ftsMatch('proposta'))->toBe([$a->id])
        ->and(ftsMatch('Proposta'))->toBe([$a->id])
        ->and(ftsMatch('ropos'))->toBe([$a->id])
        ->and(ftsMatch('acao'))->toBe([$b->id])
        ->and(ftsMatch('AÇÃO'))->toBe([$b->id])
        ->and(ftsMatch('divulgacao na'))->toBe([$b->id]);
});

test('busca frases com aspas sem quebrar a consulta', function () {
    $m = Message::factory()->text('Ela disse "pode mandar" ontem')->create();

    expect(ftsMatch('"pode mandar"'))->toBe([$m->id]);
});

test('termo de 2 caracteres usa LIKE', function () {
    $m = Message::factory()->text('Só um ok rápido')->create();
    Message::factory()->text('Nada aqui')->create();

    $ids = Message::query()
        ->whereRaw("search_text LIKE ? ESCAPE '\\'", ['%'.TextNormalizer::likeEscape('ok').'%'])
        ->pluck('id')->all();

    expect(ftsMatch('ok'))->toBe([])
        ->and($ids)->toBe([$m->id]);
});

test('indexa legenda de imagem e nome de documento, e ignora o resto', function () {
    $image = Message::factory()->ofKind(Message::IMAGE, 'IMG-0001.jpg', 'Fachada do espaço')->create();
    $doc = Message::factory()->ofKind(Message::DOC, 'Orçamento_final.pdf')->create();
    Message::factory()->system('Fachada do grupo foi alterada')->create();
    Message::factory()->ofKind(Message::DELETED, 'Fachada')->create();
    Message::factory()->ofKind(Message::AUDIO, 'Fachada.opus')->create();
    Message::factory()->ofKind(Message::STICKER, 'STK-fachada.webp')->create();
    Message::factory()->ofKind(Message::MISSING, 'fachada.jpg')->create();

    expect(ftsMatch('fachada'))->toBe([$image->id])
        ->and(ftsMatch('orcamento'))->toBe([$doc->id]);
});

test('acompanha atualização e exclusão de mensagens', function () {
    $m = Message::factory()->text('primeira versão')->create();

    $m->update(['search_text' => TextNormalizer::normalize('segunda versão')]);
    expect(ftsMatch('primeira'))->toBe([])
        ->and(ftsMatch('segunda'))->toBe([$m->id]);

    $m->delete();
    expect(ftsMatch('segunda'))->toBe([]);
});

test('apagar a conversa remove as mensagens do índice', function () {
    $conversation = Conversation::factory()->create();
    Message::factory()->for($conversation)->count(3)->text('reunião de alinhamento')->create();

    expect(ftsMatch('alinhamento'))->toHaveCount(3);

    $conversation->delete();

    expect(Message::count())->toBe(0)
        ->and(ftsMatch('alinhamento'))->toBe([]);

    DB::statement("INSERT INTO messages_fts (messages_fts) VALUES ('integrity-check')");
});

test('emojis entram na busca', function () {
    $m = Message::factory()->text('Fechado 🎉 até amanhã')->create();

    expect(ftsMatch('🎉 até'))->toBe([$m->id]);
});
