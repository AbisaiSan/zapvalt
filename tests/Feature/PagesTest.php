<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('as páginas abrem pelo Inertia', function (string $uri, string $component) {
    $this->get($uri)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    ['/', 'Home'],
    ['/importar', 'Import'],
    ['/configuracoes', 'Settings'],
]);

test('a conversa recebe o id e a mensagem de foco', function () {
    $this->get('/c/7?m=42')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Chat')
        ->where('conversationId', 7)
        ->where('focusMessageId', 42));

    $this->get('/c/7')->assertInertia(fn (Assert $page) => $page->where('focusMessageId', null));
});
