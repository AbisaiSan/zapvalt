<?php

use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PendingController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\MediaController;
use App\Models\Conversation;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Rascunhos de importação nunca aparecem: só conversas ativas resolvem a rota.
Route::bind('conversation', fn (string $id) => Conversation::query()->active()->findOrFail((int) $id));

Route::get('/', fn () => Inertia::render('Home'))->name('home');

Route::prefix('api')->group(function () {
    Route::get('archive/summary', [ArchiveController::class, 'summary']);

    Route::get('conversations', [ConversationController::class, 'index']);
    Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->whereNumber('conversation');
    Route::patch('conversations/{conversation}', [ConversationController::class, 'update'])->whereNumber('conversation');
    Route::delete('conversations/{conversation}', [ConversationController::class, 'destroy'])->whereNumber('conversation');
    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index'])->whereNumber('conversation');
    Route::get('conversations/{conversation}/days', [MessageController::class, 'days'])->whereNumber('conversation');
    Route::get('conversations/{conversation}/matches', [MessageController::class, 'matches'])->whereNumber('conversation');
    Route::get('conversations/{conversation}/media', [MessageController::class, 'media'])->whereNumber('conversation');

    Route::get('search/conversations', [SearchController::class, 'conversations']);
    Route::get('search/messages', [SearchController::class, 'messages']);

    Route::post('imports/uploads', [ImportController::class, 'startUpload']);
    Route::put('imports/uploads/{upload}', [ImportController::class, 'uploadChunk'])->whereNumber('upload');
    Route::post('imports/uploads/{upload}/complete', [ImportController::class, 'completeUpload'])->whereNumber('upload');
    Route::get('imports', [ImportController::class, 'index']);
    Route::patch('imports/files/{file}', [ImportController::class, 'updateFile'])->whereNumber('file');
    Route::post('imports/commit', [ImportController::class, 'commit']);
    Route::post('imports/cancel', [ImportController::class, 'cancel']);

    Route::get('settings', [SettingsController::class, 'show']);
    Route::put('settings', [SettingsController::class, 'update']);

    // Tarefa 09: senha, bloqueio e backup.
    Route::put('settings/password', PendingController::class);
    Route::delete('settings/password', PendingController::class);
    Route::post('unlock', PendingController::class);
    Route::post('lock', PendingController::class);
    Route::post('backups', PendingController::class);
    Route::get('backups/{backup}', PendingController::class)->whereNumber('backup');
});

Route::get('media/{attachment}', [MediaController::class, 'show'])->whereNumber('attachment');
Route::get('media/{attachment}/thumb', [MediaController::class, 'thumb'])->whereNumber('attachment');
