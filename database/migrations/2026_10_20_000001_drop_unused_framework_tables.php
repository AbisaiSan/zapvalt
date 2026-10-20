<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove do acervo as tabelas padrão do Laravel que o ZapVault não usa
     * (usuários, sessões e cache em banco) e as filas, que foram para db/queue.sqlite.
     * Em bancos novos não há nada para remover.
     */
    public function up(): void
    {
        foreach (['users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        //
    }
};
