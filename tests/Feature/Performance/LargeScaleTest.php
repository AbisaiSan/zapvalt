<?php

use Illuminate\Support\Facades\DB;

// Metas de desempenho da especificação com ~300 conversas e 1,5 milhão de mensagens.
// Leva alguns minutos e ~800 MB em disco temporário, por isso fica fora do composer test:
//   php artisan test --group=performance

/** Mediana, em milissegundos, de 5 pedidos depois de um de aquecimento. */
function medianMs(callable $request): float
{
    $request();
    $times = [];
    for ($i = 0; $i < 5; $i++) {
        $start = hrtime(true);
        $request();
        $times[] = (hrtime(true) - $start) / 1e6;
    }
    sort($times);

    return $times[2];
}

test('escala grande cumpre as metas de tempo', function () {
    useFileArchive(root: sys_get_temp_dir().'/zapvault-desempenho-'.uniqid());

    $start = microtime(true);
    $this->artisan('zapvault:demo', ['--scale' => 'large', '--force' => true])->assertSuccessful();
    $seeding = microtime(true) - $start;

    $messages = DB::table('messages')->count();
    $big = DB::table('conversations')->orderByDesc('message_count')->first();
    $name = mb_substr(DB::table('conversations')->where('id', '>', 12)->value('name'), 0, 5);
    expect(DB::table('conversations')->count())->toBeGreaterThanOrEqual(300)
        ->and($messages)->toBeGreaterThan(1_400_000)
        ->and($big->message_count)->toBeGreaterThanOrEqual(100_000);

    $results = [
        'Lista de conversas' => [medianMs(fn () => $this->getJson('/api/conversations')->assertOk()), 300],
        'Busca por palavra comum ("proposta")' => [medianMs(fn () => $this->getJson('/api/search/messages?q=proposta')->assertOk()), 500],
        "Busca por nome de conversa (\"{$name}\")" => [medianMs(fn () => $this->getJson('/api/search/conversations?q='.urlencode($name))->assertOk()), 150],
        'Abrir conversa de 100 mil mensagens' => [medianMs(function () use ($big) {
            $this->getJson("/api/conversations/{$big->id}")->assertOk();
            $this->getJson("/api/conversations/{$big->id}/messages")->assertOk();
        }), 400],
    ];

    fwrite(STDERR, sprintf("\n  %s mensagens geradas em %.0f s\n", number_format($messages, 0, ',', '.'), $seeding));
    foreach ($results as $label => [$ms, $goal]) {
        fwrite(STDERR, sprintf("  %-50s %6.0f ms (meta %d ms)\n", $label, $ms, $goal));
    }

    foreach ($results as $label => [$ms, $goal]) {
        expect($ms)->toBeLessThan($goal, $label);
    }
})->group('performance');
