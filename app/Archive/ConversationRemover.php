<?php

namespace App\Archive;

use App\Import\Media\MediaStore;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

/**
 * Apaga uma conversa do ZapVault (mensagens, participantes e anexos em cascata) e,
 * depois, os arquivos de mídia que nenhum outro anexo usa. Os exports originais não são tocados.
 */
class ConversationRemover
{
    public function delete(Conversation $conversation): void
    {
        $hashes = MediaStore::hashesForMessages(fn ($q) => $q->where('messages.conversation_id', $conversation->id));

        DB::transaction(fn () => $conversation->delete());

        (new MediaStore(config('zapvault.data_path')))->deleteUnused($hashes);
    }
}
