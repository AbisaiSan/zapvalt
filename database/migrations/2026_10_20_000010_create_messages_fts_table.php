<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Índice de busca FTS5 (trigramas) sobre messages.search_text, mantido por gatilhos.
     * Só entram mensagens com search_text preenchido.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE VIRTUAL TABLE messages_fts USING fts5(
                search_text,
                content='messages',
                content_rowid='id',
                tokenize='trigram'
            );

            CREATE TRIGGER messages_fts_insert AFTER INSERT ON messages
            WHEN new.search_text IS NOT NULL
            BEGIN
                INSERT INTO messages_fts (rowid, search_text) VALUES (new.id, new.search_text);
            END;

            CREATE TRIGGER messages_fts_delete AFTER DELETE ON messages
            WHEN old.search_text IS NOT NULL
            BEGIN
                INSERT INTO messages_fts (messages_fts, rowid, search_text) VALUES ('delete', old.id, old.search_text);
            END;

            CREATE TRIGGER messages_fts_update AFTER UPDATE OF search_text ON messages
            BEGIN
                INSERT INTO messages_fts (messages_fts, rowid, search_text)
                    SELECT 'delete', old.id, old.search_text WHERE old.search_text IS NOT NULL;
                INSERT INTO messages_fts (rowid, search_text)
                    SELECT new.id, new.search_text WHERE new.search_text IS NOT NULL;
            END;
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS messages_fts_update;
            DROP TRIGGER IF EXISTS messages_fts_delete;
            DROP TRIGGER IF EXISTS messages_fts_insert;
            DROP TABLE IF EXISTS messages_fts;
            SQL);
    }
};
