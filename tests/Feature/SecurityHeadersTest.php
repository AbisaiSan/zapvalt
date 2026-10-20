<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('as respostas trazem os cabeçalhos de segurança', function (string $uri, int $status) {
    $response = $this->get($uri)->assertStatus($status);

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'no-referrer');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("img-src 'self' data: blob:")
        ->toContain("media-src 'self' blob:")
        ->toContain("frame-src 'self'")
        ->toContain("script-src 'self'")
        ->not->toContain('unsafe-inline');
})->with([
    'página inicial' => ['/', 200],
    'página inexistente' => ['/nao-existe', 404],
    'verificação de saúde' => ['/up', 200],
]);
