<?php

use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->root = seedDemo();
});

test('mostra as configurações', function () {
    $this->getJson('/api/settings')->assertOk()->assertExactJson([
        'user_names' => ['Moura Consultoria'],
        'theme' => 'light',
        'data_path' => $this->root,
        'has_password' => false,
        'last_backup' => null,
    ]);
});

test('salva nomes e tema e recalcula quem é você', function () {
    $this->putJson('/api/settings', ['user_names' => ['Moura Consultoria', ' Maria Souza ', 'maria souza', ''], 'theme' => 'dark'])
        ->assertOk()
        ->assertJsonPath('user_names', ['Moura Consultoria', 'Maria Souza'])
        ->assertJsonPath('theme', 'dark');

    expect(Participant::query()->where('is_me', true)->distinct()->pluck('name')->sort()->values()->all())->toBe(['Maria Souza', 'Moura Consultoria']);

    $this->putJson('/api/settings', ['user_names' => []])->assertOk();
    expect(Participant::query()->where('is_me', true)->count())->toBe(0);
});

test('valida o tema', function () {
    $this->putJson('/api/settings', ['theme' => 'roxo'])->assertUnprocessable();
});

test('senha, bloqueio e backup respondem 501 até a tarefa 09', function () {
    $this->putJson('/api/settings/password', ['password' => '123456'])->assertStatus(501);
    $this->postJson('/api/unlock', ['password' => '123456'])->assertStatus(501);
    $this->postJson('/api/backups')->assertStatus(501);
});

test('erros da API vêm em JSON', function () {
    $this->get('/api/conversations/999999')->assertNotFound()->assertHeader('Content-Type', 'application/json');
});

test('mensagens de validação em português', function () {
    $this->putJson('/api/settings', ['user_names' => 'não é lista'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.user_names.0', 'O campo nomes precisa ser uma lista.');
});
