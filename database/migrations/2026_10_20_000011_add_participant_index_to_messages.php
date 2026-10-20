<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O SQLite não indexa chaves estrangeiras sozinho; sem este índice, contar mensagens
     * por participante ou apagar participantes percorre a tabela inteira.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index('participant_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['participant_id']);
        });
    }
};
