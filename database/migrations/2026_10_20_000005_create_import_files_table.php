<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_path')->nullable();
            $table->string('ext', 16);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256', 64)->nullable();
            $table->string('status', 16)->default('waiting'); // uploading, waiting, processing, done, partial, failed
            $table->unsignedTinyInteger('percent')->default(0);
            $table->foreignId('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->foreignId('existing_conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->string('choice', 16)->nullable(); // merge, separate
            $table->json('stats')->nullable();
            $table->json('unrecognized_lines')->nullable();
            $table->unsignedInteger('unrecognized_count')->default(0);
            $table->string('detected_format', 32)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_files');
    }
};
