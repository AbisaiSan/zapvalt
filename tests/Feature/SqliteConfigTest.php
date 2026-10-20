<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

test('a conexão sqlite usa WAL, synchronous NORMAL, busy_timeout e chaves estrangeiras', function () {
    $file = storage_path('framework/testing/pragmas-'.uniqid().'.sqlite');
    touch($file);

    config(['database.connections.pragmas' => array_merge(
        config('database.connections.sqlite'),
        ['database' => $file],
    )]);

    $db = DB::connection('pragmas');

    expect($db->selectOne('PRAGMA journal_mode')->journal_mode)->toBe('wal')
        ->and((int) $db->selectOne('PRAGMA synchronous')->synchronous)->toBe(1)
        ->and((int) $db->selectOne('PRAGMA busy_timeout')->timeout)->toBe(10000)
        ->and((int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);

    DB::purge('pragmas');
    File::delete([$file, "{$file}-wal", "{$file}-shm"]);
});

test('o fuso padrão é America/Sao_Paulo', function () {
    expect(config('app.timezone'))->toBe('America/Sao_Paulo');
});
