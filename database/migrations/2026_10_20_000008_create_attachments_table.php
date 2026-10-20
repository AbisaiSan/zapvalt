<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('ext', 16);
            $table->string('mime', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256', 64);
            $table->string('storage_path');
            $table->string('thumb_path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('pages')->nullable();

            // Vários anexos podem apontar para o mesmo arquivo em disco.
            $table->index('sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
