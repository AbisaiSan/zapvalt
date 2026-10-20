<?php

namespace App\Http\Controllers\Api;

use App\Archive\BackupWriter;
use App\Http\Controllers\Controller;
use App\Jobs\CreateBackup;
use App\Models\Backup;
use Illuminate\Http\JsonResponse;

class BackupController extends Controller
{
    /** Começa um backup; se já houver um em andamento, devolve esse. */
    public function store(): JsonResponse
    {
        $backup = BackupWriter::active();

        if ($backup === null) {
            $backup = Backup::query()->create(['status' => Backup::WAITING, 'percent' => 0, 'started_at' => now()]);
            CreateBackup::dispatch($backup->id);
            $backup->refresh();
        }

        return response()->json($backup->toApi(), 202);
    }

    public function show(Backup $backup): JsonResponse
    {
        return response()->json($backup->toApi());
    }
}
