<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_normalized');
            $table->boolean('is_me')->default(false);
            $table->unsignedInteger('message_count')->default(0);

            $table->unique(['conversation_id', 'name_normalized']);
            $table->index('name_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
