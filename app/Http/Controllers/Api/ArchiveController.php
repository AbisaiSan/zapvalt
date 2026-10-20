<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ArchiveController extends Controller
{
    /** Resumo do acervo; o tamanho em disco fica em cache por alguns minutos. */
    public function summary(): JsonResponse
    {
        $totals = DB::table('conversations')
            ->where('status', Conversation::ACTIVE)
            ->selectRaw('COUNT(*) AS conversations, COALESCE(SUM(message_count), 0) AS messages, MIN(first_message_at) AS first_at, MAX(last_message_at) AS last_at')
            ->first();

        $attachments = DB::table('attachments')
            ->join('messages', 'messages.id', '=', 'attachments.message_id')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.status', Conversation::ACTIVE)
            ->count();

        return response()->json([
            'conversations' => (int) $totals->conversations,
            'messages' => (int) $totals->messages,
            'first_message_at' => $totals->first_at === null ? null : str_replace(' ', 'T', $totals->first_at),
            'last_message_at' => $totals->last_at === null ? null : str_replace(' ', 'T', $totals->last_at),
            'attachments' => $attachments,
            'bytes_used' => Cache::remember('zapvault.bytes_used', now()->addMinutes(5), fn () => $this->bytesUsed()),
        ]);
    }

    /** Banco, mídia, miniaturas e originais. */
    private function bytesUsed(): int
    {
        $root = config('zapvault.data_path');
        $total = 0;

        foreach (['db', 'media', 'thumbs', 'originals'] as $dir) {
            if (! is_dir("{$root}/{$dir}")) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}", RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $total += $file->getSize();
            }
        }

        return $total;
    }
}
