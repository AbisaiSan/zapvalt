<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('cria todas as tabelas do acervo', function () {
    foreach (['settings', 'import_batches', 'import_files', 'conversations', 'participants', 'messages', 'attachments', 'backups', 'messages_fts'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("tabela {$table}");
    }
});

test('não mantém tabelas de usuários, sessões, cache ou filas no acervo', function () {
    foreach (['users', 'sessions', 'cache', 'jobs', 'failed_jobs'] as $table) {
        expect(Schema::hasTable($table))->toBeFalse("tabela {$table}");
    }
});

test('as filas ficam na conexão queue', function () {
    foreach (['jobs', 'job_batches', 'failed_jobs'] as $table) {
        expect(Schema::connection('queue')->hasTable($table))->toBeTrue("tabela {$table}");
    }

    expect(config('queue.connections.database.connection'))->toBe('queue')
        ->and(config('queue.failed.database'))->toBe('queue')
        ->and(config('queue.batching.database'))->toBe('queue');
});

test('cria os índices de messages e conversations', function () {
    $indexes = collect(DB::select("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name IN ('messages', 'conversations')"))
        ->pluck('sql', 'name');

    expect($indexes->keys()->all())->toContain(
        'messages_conversation_id_sent_at_id_index',
        'messages_conversation_id_dedupe_key_unique',
        'messages_import_file_id_index',
        'messages_media_index',
        'conversations_status_last_message_at_index',
        'conversations_name_normalized_index',
    );

    expect($indexes['messages_media_index'])->toContain("WHERE kind IN ('image', 'video')");
});

test('cria a tabela FTS5 com trigramas e os três gatilhos', function () {
    $fts = DB::selectOne("SELECT sql FROM sqlite_master WHERE name = 'messages_fts'")->sql;
    $triggers = collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'trigger'"))->pluck('name')->all();

    expect($fts)->toContain("tokenize='trigram'")->toContain("content='messages'")
        ->and($triggers)->toContain('messages_fts_insert', 'messages_fts_delete', 'messages_fts_update');
});
