<?php

use App\Models\Attachment;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->root = seedDemo();
    $this->image = Attachment::query()->where('original_name', 'IMG-20261019-WA0007.jpg')->sole();
});

test('entrega a imagem com tipo, ETag, cache e nosniff', function () {
    $response = $this->get("/media/{$this->image->id}")->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('image/jpeg')
        ->and($response->headers->get('ETag'))->toBe('"'.$this->image->sha256.'"')
        ->and($response->headers->get('Cache-Control'))->toContain('private')->toContain('max-age=31536000')->toContain('immutable')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Content-Disposition'))->toStartWith('inline');
});

test('responde 206 com Content-Range', function () {
    $response = $this->get("/media/{$this->image->id}", ['Range' => 'bytes=0-99']);

    $response->assertStatus(206);
    expect($response->headers->get('Content-Range'))->toBe("bytes 0-99/{$this->image->size_bytes}")
        ->and($response->headers->get('Content-Length'))->toBe('100');
});

test('intervalo inválido dá 416', function (string $range) {
    $response = $this->get("/media/{$this->image->id}", ['Range' => $range]);

    $response->assertStatus(416);
    expect($response->headers->get('Content-Range'))->toBe("bytes */{$this->image->size_bytes}");
})->with(['bytes=99999999-', 'bytes=99999999-999999999', 'bytes=500-100']);

test('responde 304 quando o ETag confere', function () {
    $this->get("/media/{$this->image->id}", ['If-None-Match' => '"'.$this->image->sha256.'"'])->assertStatus(304);
});

test('download=1 força o download com o nome original', function () {
    $pdf = Attachment::query()->where('original_name', 'Proposta_v3.pdf')->sole();

    expect($this->get("/media/{$pdf->id}")->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($this->get("/media/{$pdf->id}")->headers->get('Content-Type'))->toBe('application/pdf');

    $download = $this->get("/media/{$pdf->id}?download=1");

    expect($download->headers->get('Content-Disposition'))->toBe('attachment; filename=Proposta_v3.pdf')
        ->and($download->headers->get('Content-Type'))->toBe('application/octet-stream');
});

test('tipos fora da lista, como HTML e SVG, são sempre baixados', function (string $name) {
    $attachment = Attachment::query()->where('original_name', 'Contatos_parceiros.csv')->sole();
    $attachment->update(['original_name' => $name, 'ext' => pathinfo($name, PATHINFO_EXTENSION)]);

    $response = $this->get("/media/{$attachment->id}");

    expect($response->headers->get('Content-Type'))->toBe('application/octet-stream')
        ->and($response->headers->get('Content-Disposition'))->toStartWith('attachment');
})->with(['pagina.html', 'desenho.svg', 'script.js', 'planilha.csv']);

test('miniatura da imagem e da figurinha', function () {
    $this->get("/media/{$this->image->id}/thumb")->assertOk()->assertHeader('Content-Type', 'image/jpeg');

    $sticker = Attachment::query()->where('ext', 'webp')->first();
    $this->get("/media/{$sticker->id}/thumb")->assertOk()->assertHeader('Content-Type', 'image/webp');

    $pdf = Attachment::query()->where('ext', 'pdf')->first();
    $this->get("/media/{$pdf->id}/thumb")->assertNotFound();
});

test('recusa caminho fora da pasta de dados', function () {
    file_put_contents(storage_path('framework/testing/fora.txt'), 'segredo');
    $this->image->update(['storage_path' => '../../fora.txt']);

    $this->get("/media/{$this->image->id}")->assertNotFound();

    $this->image->update(['storage_path' => '/etc/hosts']);
    $this->get("/media/{$this->image->id}")->assertNotFound();

    @unlink(storage_path('framework/testing/fora.txt'));
});

test('arquivo ausente no disco e anexo de rascunho dão 404', function () {
    unlink($this->root.'/'.$this->image->storage_path);
    $this->get("/media/{$this->image->id}")->assertNotFound();

    $other = Attachment::query()->where('original_name', 'Proposta_v3.pdf')->sole();
    Conversation::query()->whereKey($other->message->conversation_id)->update(['status' => 'draft']);
    $this->get("/media/{$other->id}")->assertNotFound();
});

test('as respostas de mídia também levam os cabeçalhos de segurança', function () {
    $response = $this->get("/media/{$this->image->id}");

    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'")
        ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
});
