<?php

use Inertia\Testing\AssertableInertia as Assert;

test('a página inicial abre o componente Home pelo Inertia', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Home'));
});

test('a página inicial carrega o script de tema antes da aplicação', function () {
    $html = $this->get('/')->getContent();

    expect($html)
        ->toContain('lang="pt-BR"')
        ->toContain('data-zv-theme="light"')
        ->toContain('js/theme.js');
});
