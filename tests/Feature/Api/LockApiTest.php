<?php

use App\Models\Setting;
use App\Support\AppLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    useTempDataPath();
});

/** Ativa a senha e começa uma sessão nova, ainda bloqueada (como ao abrir o navegador). */
function activatePassword(string $password = 'segredo1'): void
{
    test()->putJson('/api/settings/password', ['password' => $password, 'password_confirmation' => $password])->assertNoContent();
    test()->flushSession();
}

test('sem senha, tudo abre e /desbloquear volta ao início', function () {
    $this->getJson('/api/conversations')->assertOk();
    $this->get('/desbloquear')->assertRedirect('/');
    $this->postJson('/api/unlock', ['password' => 'qualquer'])->assertNoContent();
});

test('a senha precisa de 6 caracteres e de confirmação', function () {
    $this->putJson('/api/settings/password', ['password' => '12345', 'password_confirmation' => '12345'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.password.0', 'Use pelo menos 6 caracteres.');

    $this->putJson('/api/settings/password', ['password' => '123456', 'password_confirmation' => '654321'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.password.0', 'As senhas não coincidem.');

    $this->getJson('/api/settings')->assertJsonPath('has_password', false);
});

test('ativar a senha mantém esta sessão aberta e guarda só o hash', function () {
    $this->putJson('/api/settings/password', ['password' => 'segredo1', 'password_confirmation' => 'segredo1'])->assertNoContent();

    $this->getJson('/api/settings')->assertOk()->assertJsonPath('has_password', true);
    expect(Setting::get('password_hash'))->not->toContain('segredo1');
});

test('com a senha ativa, nada abre sem desbloquear', function () {
    activatePassword();

    foreach (['/', '/c/1', '/importar', '/configuracoes'] as $page) {
        $this->get($page)->assertRedirect('/desbloquear');
    }

    foreach (['/api/conversations', '/api/archive/summary', '/api/settings', '/api/search/messages?q=oi', '/api/imports', '/media/1', '/media/1/thumb'] as $uri) {
        $this->getJson($uri)->assertStatus(423)->assertJsonPath('message', 'O ZapVault está bloqueado. Digite a senha para continuar.');
    }
    $this->get('/media/1')->assertStatus(423);
    $this->postJson('/api/backups')->assertStatus(423);
    $this->putJson('/api/settings', ['theme' => 'dark'])->assertStatus(423);
    $this->deleteJson('/api/settings/password', ['password' => 'segredo1'])->assertStatus(423);

    $this->get('/desbloquear')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Unlock'));
});

test('a senha certa desbloqueia, a errada não', function () {
    activatePassword();

    $this->postJson('/api/unlock', ['password' => 'errada1'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Senha incorreta.')
        ->assertJsonPath('retry_after', 0);
    $this->getJson('/api/conversations')->assertStatus(423);

    $this->postJson('/api/unlock', ['password' => 'segredo1'])->assertNoContent();
    $this->getJson('/api/conversations')->assertOk();
    $this->get('/')->assertOk();
    $this->get('/desbloquear')->assertRedirect('/');
});

test('depois de 5 senhas erradas, o atraso cresce até 60 segundos', function () {
    activatePassword();

    foreach ([0, 0, 0, 0] as $wait) {
        $this->postJson('/api/unlock', ['password' => 'errada1'])->assertJsonPath('retry_after', $wait);
    }
    $this->postJson('/api/unlock', ['password' => 'errada1'])
        ->assertUnprocessable()
        ->assertJsonPath('retry_after', 5)
        ->assertJsonPath('message', 'Muitas tentativas erradas. Aguarde 5 segundos para tentar de novo.');

    // Durante a espera, nem a senha certa é conferida.
    $this->postJson('/api/unlock', ['password' => 'segredo1'])->assertUnprocessable()->assertJsonPath('retry_after', 5);

    $this->travel(6)->seconds();
    $this->postJson('/api/unlock', ['password' => 'errada1'])->assertJsonPath('retry_after', 10);

    expect(AppLock::delayFor(7))->toBe(20)
        ->and(AppLock::delayFor(8))->toBe(40)
        ->and(AppLock::delayFor(9))->toBe(60)
        ->and(AppLock::delayFor(30))->toBe(60);

    $this->travel(11)->seconds();
    $this->postJson('/api/unlock', ['password' => 'segredo1'])->assertNoContent();

    // Acertar zera a contagem.
    $this->postJson('/api/lock')->assertNoContent();
    $this->postJson('/api/unlock', ['password' => 'errada1'])->assertJsonPath('retry_after', 0);
});

test('Bloquear agora fecha a sessão', function () {
    activatePassword();
    $this->postJson('/api/unlock', ['password' => 'segredo1'])->assertNoContent();

    $this->postJson('/api/lock')->assertNoContent();

    $this->getJson('/api/conversations')->assertStatus(423);
    $this->get('/')->assertRedirect('/desbloquear');
});

test('o desbloqueio vale no máximo 12 horas', function () {
    activatePassword();
    $this->postJson('/api/unlock', ['password' => 'segredo1'])->assertNoContent();

    $this->travel(11)->hours();
    $this->getJson('/api/conversations')->assertOk();

    $this->travel(1)->hours();
    $this->getJson('/api/conversations')->assertStatus(423);
});

test('trocar a senha bloqueia as sessões abertas com a senha antiga', function () {
    activatePassword();
    $this->postJson('/api/unlock', ['password' => 'segredo1'])->assertNoContent();
    $old = session()->all();

    $this->putJson('/api/settings/password', ['password' => 'novasenha', 'password_confirmation' => 'novasenha'])->assertNoContent();
    $this->getJson('/api/conversations')->assertOk();

    // Outra janela, ainda com a marca da senha antiga.
    $this->flushSession();
    $this->withSession($old)->getJson('/api/conversations')->assertStatus(423);
});

test('desativar a senha exige a senha atual', function () {
    activatePassword();
    $this->postJson('/api/unlock', ['password' => 'segredo1'])->assertNoContent();

    $this->deleteJson('/api/settings/password', ['password' => 'errada1'])->assertUnprocessable()->assertJsonPath('message', 'Senha incorreta.');
    $this->getJson('/api/settings')->assertJsonPath('has_password', true);

    $this->deleteJson('/api/settings/password', ['password' => 'segredo1'])->assertNoContent();
    $this->getJson('/api/settings')->assertJsonPath('has_password', false);

    $this->flushSession();
    $this->getJson('/api/conversations')->assertOk();
});
