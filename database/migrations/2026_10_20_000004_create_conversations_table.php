<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_normalized');
            $table->boolean('is_group')->default(false);
            $table->string('phone')->nullable();
            $table->unsignedTinyInteger('avatar_index')->default(0);
            $table->string('status', 16)->default('draft'); // draft, active
            $table->string('source_file_name')->nullable();
            $table->dateTime('first_message_at')->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
            $table->index('name_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
