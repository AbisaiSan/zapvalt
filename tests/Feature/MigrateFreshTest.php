<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

// Sem RefreshDatabase: migrate:fresh não roda dentro de transação.
test('migrate:fresh funciona no ambiente de testes', function () {
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');

    expect(Artisan::call('migrate:fresh'))->toBe(0)
        ->and(Schema::hasTable('messages_fts'))->toBeTrue()
        ->and(Schema::connection('queue')->hasTable('jobs'))->toBeTrue();
});
