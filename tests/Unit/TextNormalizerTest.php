<?php

use App\Support\TextNormalizer;

test('ignora maiúsculas e minúsculas', function () {
    expect(TextNormalizer::normalize('PROPOSTA'))->toBe('proposta')
        ->and(TextNormalizer::normalize('Proposta'))->toBe('proposta')
        ->and(TextNormalizer::normalize('proposta'))->toBe('proposta');
});

test('remove acentos do português e de outras línguas', function (string $input, string $expected) {
    expect(TextNormalizer::normalize($input))->toBe($expected);
})->with([
    ['ação', 'acao'],
    ['AÇÃO', 'acao'],
    ['Coração', 'coracao'],
    ['você está ótimo', 'voce esta otimo'],
    ['ç', 'c'],
    ['ñ', 'n'],
    ['ü', 'u'],
    ['Ñandú pingüino', 'nandu pinguino'],
]);

test('mantém emojis e aspas curvas', function () {
    expect(TextNormalizer::normalize('Ótimo 👍🏽 “sim”'))->toBe('otimo 👍🏽 “sim”');
});

test('remove caracteres invisíveis e troca espaços especiais', function () {
    $text = "\u{200E}Maria\u{00A0}Souza\u{202F}às\u{200F} 9h\u{FEFF}\u{202A}x\u{202C}";

    expect(TextNormalizer::normalize($text))->toBe('maria souza as 9hx')
        ->and(TextNormalizer::clean($text))->toBe('Maria Souza às 9hx');
});

test('monta a frase FTS com aspas internas dobradas', function () {
    expect(TextNormalizer::ftsPhrase('disse "oi"'))->toBe('"disse ""oi"""');
});

test('escapa curingas do LIKE', function () {
    expect(TextNormalizer::likeEscape('50%_a\\b'))->toBe('50\\%\\_a\\\\b');
});
