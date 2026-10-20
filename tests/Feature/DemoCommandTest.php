<?php

use App\Demo\DemoBuilder;
use App\Demo\DemoData;
use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->root = useTempDataPath();
    Carbon::setTestNow('2026-10-20 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('cria as 12 conversas do protótipo com os números dele', function () {
    $this->artisan('zapvault:demo')->assertSuccessful();

    $expected = [
        'Maria Souza' => 30, 'Grupo Projeto Alfa' => 28, 'João Pereira' => 6, 'Gráfica Rápida' => 6,
        'Grupo Financeiro' => 6, 'Fernanda Lima' => 5, 'Transportadora Norte' => 3, 'Ricardo Alves' => 4,
        'Clínica Vida' => 4, 'Grupo Parceiros' => 7, 'Paulo Mendes' => 3, '+55 98 91234-5678' => 2,
    ];

    $conversations = Conversation::query()->active()->orderByDesc('last_message_at')->get();

    expect($conversations->pluck('name')->all())->toBe(array_keys($expected))
        ->and($conversations->mapWithKeys(fn ($c) => [$c->name => $c->messages()->count()])->all())->toBe($expected)
        ->and(Message::query()->count())->toBe(104)
        ->and($conversations->where('is_group', true)->pluck('name')->sort()->values()->all())->toBe(['Grupo Financeiro', 'Grupo Parceiros', 'Grupo Projeto Alfa'])
        ->and($conversations->firstWhere('name', 'Maria Souza')->avatar_index)->toBe(0)
        ->and($conversations->firstWhere('name', 'Grupo Projeto Alfa')->avatar_index)->toBe(2)
        ->and($conversations->firstWhere('name', 'João Pereira')->source_file_name)->toBe('Conversa com João Pereira.txt')
        ->and($conversations->firstWhere('name', '+55 98 91234-5678')->phone)->toBe('+55 98 91234-5678')
        ->and(Setting::userNames())->toBe([DemoData::ME]);
});

test('anexos de exemplo têm arquivo, dimensões, duração e páginas', function () {
    $this->artisan('zapvault:demo')->assertSuccessful();

    $image = Attachment::query()->where('original_name', 'IMG-20261019-WA0007.jpg')->sole();
    $pdf = Attachment::query()->where('original_name', 'Proposta_v3.pdf')->sole();
    $audio = Attachment::query()->whereHas('message', fn ($q) => $q->where('kind', 'audio'))->orderBy('id')->first();

    expect([$image->width, $image->height])->toBe([1200, 900])
        ->and($image->thumb_path)->not->toBeNull()
        ->and($pdf->pages)->toBe(3)
        ->and($pdf->size_bytes)->toBe(240 * 1024)
        ->and($audio->duration_seconds)->toBe(42)
        ->and(Message::query()->where('kind', 'image')->count())->toBe(7)
        ->and(Attachment::query()->count())->toBe(22);

    foreach (Attachment::all() as $attachment) {
        expect(is_file($this->root.'/'.$attachment->storage_path))->toBeTrue();
    }
});

test('zapvault:verify fica limpo depois da demonstração', function () {
    $this->artisan('zapvault:demo')->assertSuccessful();

    $this->artisan('zapvault:verify', ['--hashes' => true])
        ->expectsOutputToContain('Nenhuma inconsistência encontrada')
        ->assertSuccessful();
});

test('desloca as datas do protótipo para a data real', function () {
    Carbon::setTestNow('2026-11-02 08:00:00');

    $this->artisan('zapvault:demo')->assertSuccessful();

    expect(Conversation::query()->where('name', 'Maria Souza')->sole()->last_message_at->format('Y-m-d H:i:s'))->toBe('2026-11-02 09:21:00');
});

test('recusa rodar sobre um acervo com conversas sem confirmação', function () {
    Conversation::factory()->create(['name' => 'Conversa real']);

    $this->artisan('zapvault:demo', ['--no-interaction' => true])
        ->expectsOutputToContain('use --force')
        ->assertFailed();

    expect(Conversation::query()->count())->toBe(1);
});

test('pergunta antes de misturar com conversas existentes', function () {
    Conversation::factory()->create(['name' => 'Conversa real']);

    $this->artisan('zapvault:demo')
        ->expectsConfirmation('O acervo já tem 1 conversa(s). Os dados fictícios serão misturados a elas. Continuar?', 'no')
        ->assertFailed();

    expect(Conversation::query()->count())->toBe(1);
});

test('rodar de novo com --force não duplica nada', function () {
    $this->artisan('zapvault:demo')->assertSuccessful();
    $this->artisan('zapvault:demo', ['--force' => true])->assertSuccessful();

    expect(Conversation::query()->count())->toBe(12)
        ->and(Message::query()->count())->toBe(104)
        ->and(Attachment::query()->count())->toBe(22)
        ->and(filesUnder($this->root.'/uploads-tmp'))->toBe([])
        ->and(filesUnder($this->root.'/originals'))->toBe([]);
});

test('recusa escala desconhecida', function () {
    $this->artisan('zapvault:demo', ['--scale' => 'enorme'])->assertFailed();
});

test('gera conversa sintética no formato do WhatsApp', function () {
    $builder = new DemoBuilder($this->root.'/uploads-tmp/teste');
    $path = $builder->syntheticText('Teste', ['Ana Lima'], 500, CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-03-01'), 7);

    $lines = file($path, FILE_IGNORE_NEW_LINES);

    expect($lines)->toHaveCount(500)
        ->and($lines[0])->toMatch('~^\d{2}/\d{2}/2026 \d{2}:\d{2} - (Ana Lima|Moura Consultoria): .+~');
});
