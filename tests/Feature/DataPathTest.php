<?php

use App\Support\DataPath;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->root = storage_path('framework/testing/data-path-'.uniqid());
});

afterEach(function () {
    File::deleteDirectory($this->root);
});

test('cria a pasta de dados com permissão restrita e as subpastas', function () {
    DataPath::ensure($this->root);

    expect(substr(sprintf('%o', fileperms($this->root)), -3))->toBe('700');

    foreach (DataPath::SUBDIRS as $dir) {
        expect(is_dir("{$this->root}/{$dir}"))->toBeTrue();
    }
});

test('cria os arquivos de banco quando ele fica dentro da pasta de dados', function () {
    DataPath::ensure($this->root, ["{$this->root}/db/zapvault.sqlite", "{$this->root}/db/queue.sqlite"]);

    expect(file_exists("{$this->root}/db/zapvault.sqlite"))->toBeTrue()
        ->and(file_exists("{$this->root}/db/queue.sqlite"))->toBeTrue();
});

test('não cria banco fora da pasta de dados', function () {
    DataPath::ensure($this->root, [':memory:']);

    expect(file_exists(':memory:'))->toBeFalse();
});

test('resolve o caminho configurado', function () {
    expect(DataPath::resolve('/tmp/ZapVault-teste/'))->toBe('/tmp/ZapVault-teste')
        ->and(DataPath::resolve('storage/x'))->toBe(base_path('storage/x'))
        ->and(DataPath::resolve(''))->toEndWith('/ZapVault-dados')
        ->and(DataPath::resolve(null))->toEndWith('/ZapVault-dados');
});

test('a aplicação usa a pasta de dados dos testes, fora do acervo real', function () {
    expect(config('zapvault.data_path'))->toBe(base_path('storage/framework/testing/zapvault-data'))
        ->and(is_dir(config('zapvault.data_path').'/db'))->toBeTrue();
});
