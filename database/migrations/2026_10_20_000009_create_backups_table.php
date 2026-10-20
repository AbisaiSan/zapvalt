<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('status', 16)->default('waiting'); // waiting, running, done, failed
            $table->unsignedTinyInteger('percent')->default(0);
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
