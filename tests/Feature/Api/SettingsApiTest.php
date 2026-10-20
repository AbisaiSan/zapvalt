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
        'current_backup' => null,
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

test('erros da API vêm em JSON', function () {
    $this->get('/api/conversations/999999')->assertNotFound()->assertHeader('Content-Type', 'application/json');
});

test('mensagens de validação em português', function () {
    $this->putJson('/api/settings', ['user_names' => 'não é lista'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.user_names.0', 'O campo nomes precisa ser uma lista.');
});

test('mensagens de erro do framework saem em português', function () {
    $this->getJson('/api/conversations/999999')->assertNotFound()->assertExactJson(['message' => 'Não encontrado. O item pode ter sido excluído do ZapVault.']);
    $this->postJson('/api/archive/summary')->assertStatus(405)->assertJsonPath('message', 'Operação não permitida.');
    $this->putJson('/api/settings/password', [])->assertUnprocessable()->assertJsonPath('message', 'Digite a nova senha.');
    $this->putJson('/api/settings', ['user_names' => 'x', 'theme' => 'roxo'])->assertUnprocessable()->assertJsonPath('message', 'O campo nomes precisa ser uma lista. (e mais 1 erro)');

    // Mensagens escritas pelo ZapVault não mudam.
    $id = conversationId('Maria Souza');
    $this->patchJson("/api/conversations/{$id}", ['name' => '   '])->assertUnprocessable()->assertJsonPath('message', 'Informe um nome para a conversa.');
});
