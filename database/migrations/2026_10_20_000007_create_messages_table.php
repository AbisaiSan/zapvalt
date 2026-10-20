<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('sent_at'); // horário local do arquivo, sem fuso
            $table->string('kind', 16); // text, image, video, audio, doc, sticker, system, call, deleted, missing
            $table->text('body')->nullable();
            $table->text('caption')->nullable();
            $table->string('system_icon', 16)->nullable();
            $table->text('search_text')->nullable();
            $table->string('dedupe_key', 80);
            $table->unsignedInteger('source_line')->nullable();
            $table->foreignId('import_file_id')->nullable()->constrained()->nullOnDelete();

            $table->index(['conversation_id', 'sent_at', 'id']);
            $table->unique(['conversation_id', 'dedupe_key']);
            $table->index('import_file_id');
        });

        // Para o visualizador de imagens e vídeos.
        DB::statement("CREATE INDEX messages_media_index ON messages (conversation_id, sent_at) WHERE kind IN ('image', 'video')");
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
