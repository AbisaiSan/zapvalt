<?php

use App\Import\ChatParser;
use App\Import\ClassifiedMessage;
use App\Import\Media\MediaSource;
use App\Import\Media\NoMedia;
use App\Import\MessageClassifier;

/**
 * @return array{0: ChatParser, 1: list<ClassifiedMessage>}
 */
function parseText(string $text, ?MediaSource $media = null): array
{
    $parser = new ChatParser(function () use ($text) {
        $handle = fopen('php://memory', 'w+b');
        fwrite($handle, $text);
        rewind($handle);

        return $handle;
    });

    if (! $parser->inspect()) {
        return [$parser, []];
    }

    $classifier = new MessageClassifier;
    $messages = [];
    foreach ($parser->messages() as $raw) {
        $messages[] = $classifier->classify($raw, $media ?? new NoMedia);
    }

    return [$parser, $messages];
}

function parseFixture(string $name): array
{
    return parseText((string) file_get_contents(exportFixture($name)));
}

test('lê o formato Android de 24 horas com mensagens de várias linhas', function () {
    [$parser, $messages] = parseFixture('Conversa do WhatsApp com Maria Souza.txt');

    expect($parser->format)->toBe(ChatParser::ANDROID_24H)
        ->and($messages)->toHaveCount(16)
        ->and($messages[1]->sentAt)->toBe('2026-10-19 09:14:00')
        ->and($messages[1]->sender)->toBe('Maria Souza')
        ->and($messages[1]->body)->toBe('Bom dia! Consegue me mandar a proposta?')
        ->and($messages[8]->body)->toBe("Precisamos de:\n• 120 cadeiras\n• uma mesa para o coffee break");
});

test('classifica aviso de criptografia, chamada, apagada, mídia oculta e anexos', function () {
    [, $m] = parseFixture('Conversa do WhatsApp com Maria Souza.txt');

    expect([$m[0]->kind, $m[0]->systemIcon, $m[0]->sender])->toBe(['system', 'lock', null])
        ->and([$m[4]->kind, $m[4]->body, $m[4]->caption])->toBe(['missing', 'IMG-20261019-WA0007.jpg', 'Esse é o espaço'])
        ->and($m[4]->dedupeKind)->toBe('image')
        ->and($m[5]->dedupeKind)->toBe('audio')
        ->and($m[6]->kind)->toBe('deleted')
        ->and([$m[7]->kind, $m[7]->sender])->toBe(['call', null])
        ->and($m[9]->dedupeKind)->toBe('sticker')
        ->and([$m[13]->kind, $m[13]->body])->toBe(['missing', null])
        ->and($m[14]->dedupeKind)->toBe('doc')
        ->and([$m[15]->dedupeKind, $m[15]->caption])->toBe(['video', 'Montagem do ano passado']);
});

test('lê o formato Android com AM e PM, ano de 2 dígitos e mês primeiro', function () {
    [$parser, $m] = parseFixture('eduardo-am-pm.txt');

    expect($parser->format)->toBe(ChatParser::ANDROID_AMPM)
        ->and($parser->dayFirst)->toBeFalse()
        ->and($m)->toHaveCount(6)
        ->and($m[0]->sentAt)->toBe('2026-10-15 19:50:00')
        ->and($m[2]->sentAt)->toBe('2026-10-16 00:05:00')
        ->and($m[3]->sentAt)->toBe('2026-10-16 12:30:00')
        ->and($m[3]->kind)->toBe('missing')
        ->and($m[4]->kind)->toBe('deleted');
});

test('lê o formato do iPhone com BOM, segundos, marcas invisíveis e avisos', function () {
    [$parser, $m] = parseFixture('_chat.txt');

    expect($parser->format)->toBe(ChatParser::IOS)
        ->and($m)->toHaveCount(12)
        ->and([$m[0]->kind, $m[0]->systemIcon])->toBe(['system', 'lock'])
        ->and([$m[1]->kind, $m[1]->sender])->toBe(['system', null])
        ->and($m[3]->sentAt)->toBe('2026-10-18 10:05:12')
        ->and($m[3]->body)->toBe('Bom dia, pessoal 👋')
        ->and($m[4]->sender)->toBe('Ana Ribeiro 🌻')
        ->and($m[4]->body)->toBe("Bom dia!\nTudo certo para hoje?")
        ->and([$m[5]->kind, $m[5]->body, $m[5]->dedupeKind])->toBe(['missing', '00000012-PHOTO-2026-10-18-10-07-30.jpg', 'image'])
        ->and($m[6]->kind)->toBe('missing')
        ->and($m[7]->kind)->toBe('deleted')
        ->and([$m[8]->kind, $m[8]->sender])->toBe(['call', null])
        ->and([$m[9]->kind, $m[9]->body])->toBe(['system', 'Luciana Prado saiu'])
        ->and($m[10]->kind)->toBe('system')
        ->and($m[11]->sender)->toBe('Rafael Moura')
        ->and($m[11]->body)->toBe('Reunião às 15h em ponto');
});

test('linhas malformadas vão para a lista de não reconhecidas, com número e texto', function () {
    [$parser, $m] = parseFixture('linhas-invalidas.txt');

    expect($m)->toHaveCount(5)
        ->and($m[2]->body)->toBe("Tudo\ncontinuação normal")
        ->and($parser->unrecognizedCount)->toBe(2)
        ->and($parser->unrecognized)->toBe([
            ['line' => 2, 'text' => '13/10/2026 17:3 - Paula Alves: linha quebrada'],
            ['line' => 4, 'text' => '32/10/2026 10:00 - Paula Alves: data impossível'],
        ]);
});

test('guarda no máximo 200 linhas não reconhecidas, mas conta todas', function () {
    $text = "01/10/2026 10:00 - Ana: oi\n".str_repeat("01/10/2026 10:0 - quebrada\n", 250).implode("\n", array_fill(0, 5, '01/10/2026 10:01 - Ana: ok'));

    [$parser] = parseText($text);

    expect($parser->unrecognizedCount)->toBe(250)
        ->and($parser->unrecognized)->toHaveCount(200);
});

test('recusa texto que não é conversa', function () {
    [$parser, $m] = parseFixture('nao-e-conversa.txt');

    expect($parser->format)->toBeNull()->and($m)->toBe([]);
});

test('aceita arquivo curto quando todas as linhas casam', function () {
    [$parser, $m] = parseText("19/10/2026 09:14 - Ana: oi\n19/10/2026 09:15 - Bia: olá\n");

    expect($m)->toHaveCount(2);
});

test('precisa de 5 linhas reconhecidas entre as 300 primeiras', function () {
    $noise = str_repeat("texto solto\n", 296);
    $four = str_repeat("19/10/2026 09:14 - Ana: oi\n", 4);
    $five = str_repeat("19/10/2026 09:14 - Ana: oi\n", 5);

    expect(parseText($four.$noise)[0]->format)->toBeNull()
        ->and(parseText($five.$noise)[0]->format)->toBe(ChatParser::ANDROID_24H);
});

test('sem evidência, a data é dia primeiro', function () {
    [, $m] = parseText(str_repeat("05/06/2026 09:14 - Ana: oi\n", 5));

    expect($m[0]->sentAt)->toBe('2026-06-05 09:14:00');
});

test('decide o tipo pelo arquivo anexado quando ele está no pacote', function (string $file, string $kind) {
    $media = new class implements MediaSource
    {
        public function has(string $fileName): bool
        {
            return true;
        }

        public function open(string $fileName)
        {
            return fopen('php://memory', 'rb');
        }
    };

    [, $m] = parseText(str_repeat("19/10/2026 09:14 - Ana: {$file} (arquivo anexado)\n", 5), $media);

    expect($m[0]->kind)->toBe($kind);
})->with([
    ['IMG-1.jpg', 'image'], ['foto.JPEG', 'image'], ['a.png', 'image'], ['a.gif', 'image'], ['a.webp', 'image'],
    ['VID-1.mp4', 'video'], ['a.mov', 'video'], ['a.3gp', 'video'], ['a.mkv', 'video'], ['a.webm', 'video'],
    ['PTT-1.opus', 'audio'], ['a.ogg', 'audio'], ['a.m4a', 'audio'], ['a.mp3', 'audio'], ['a.aac', 'audio'], ['a.amr', 'audio'], ['a.wav', 'audio'],
    ['STK-20261019-WA0003.webp', 'sticker'], ['00000034-STICKER-2026.webp', 'sticker'],
    ['Proposta.pdf', 'doc'], ['planilha.xlsx', 'doc'], ['pagina.html', 'doc'], ['sem-extensao.x', 'doc'],
]);

test('anexo citado mas fora do pacote vira mídia faltante com o nome guardado', function () {
    [, $m] = parseText(str_repeat("19/10/2026 09:14 - Ana: <anexado: 0001-PHOTO.jpg>\n", 5));

    expect([$m[0]->kind, $m[0]->body, $m[0]->dedupeKind])->toBe(['missing', '0001-PHOTO.jpg', 'image']);
});

test('textos em inglês de anexo, mídia oculta e apagada', function () {
    [, $m] = parseText(implode("\n", [
        '19/10/2026 09:14 - Ana: IMG-1.jpg (file attached)',
        '19/10/2026 09:15 - Ana: <attached: 0002-PHOTO.jpg>',
        '19/10/2026 09:16 - Ana: <Media omitted>',
        '19/10/2026 09:17 - Ana: You deleted this message',
        '19/10/2026 09:18 - Ana: Você apagou esta mensagem',
    ]));

    expect(array_map(fn ($x) => $x->kind, $m))->toBe(['missing', 'missing', 'missing', 'deleted', 'deleted'])
        ->and($m[0]->body)->toBe('IMG-1.jpg')
        ->and($m[1]->body)->toBe('0002-PHOTO.jpg');
});
