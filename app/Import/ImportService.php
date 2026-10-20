<?php

namespace App\Import;

use App\Import\Media\MediaStore;
use App\Models\Conversation;
use App\Models\ImportBatch;
use App\Models\ImportFile;
use App\Models\Setting;
use App\Support\TextNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Lotes de importação: adicionar arquivos, Concluir (fase 2) e Cancelar.
 */
class ImportService
{
    private string $root;

    public function __construct()
    {
        $this->root = config('zapvault.data_path');
    }

    public function openBatch(): ?ImportBatch
    {
        return ImportBatch::query()->where('status', ImportBatch::OPEN)->latest('id')->first();
    }

    public function openOrCreateBatch(): ImportBatch
    {
        return $this->openBatch() ?? ImportBatch::query()->create(['status' => ImportBatch::OPEN]);
    }

    /**
     * Guarda o original em {DATA}/originals/{lote}/ e cria o cartão do arquivo, aguardando análise.
     */
    public function addFile(ImportBatch $batch, string $path, ?string $originalName = null): ImportFile
    {
        $originalName = basename($originalName ?? $path);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! in_array($ext, ['txt', 'zip'], true)) {
            throw new ImportException('Só é possível importar arquivos .txt ou .zip.');
        }

        if (! is_file($path) || ! is_readable($path)) {
            throw new ImportException("Arquivo não encontrado: {$originalName}");
        }

        $isZip = str_starts_with((string) file_get_contents($path, false, null, 0, 4), 'PK');

        if ($ext === 'zip' && ! $isZip) {
            throw new ImportException('O arquivo .zip está corrompido ou não é um .zip de verdade.');
        }

        $size = (int) filesize($path);
        $free = @disk_free_space($this->root);

        if ($free !== false && $free < $size * 2) {
            throw new ImportException('Não há espaço livre suficiente no disco para importar este arquivo.');
        }

        $relative = "originals/{$batch->id}/".Str::random(8).'-'.$this->safeName($originalName);
        File::ensureDirectoryExists(dirname($this->root.'/'.$relative), 0700);
        copy($path, $this->root.'/'.$relative);

        return ImportFile::query()->create([
            'batch_id' => $batch->id,
            'original_name' => $originalName,
            'stored_path' => $relative,
            'ext' => $ext,
            'size_bytes' => $size,
            'sha256' => hash_file('sha256', $path),
            'status' => ImportFile::WAITING,
        ]);
    }

    /** Todos os arquivos analisados, nenhuma escolha pendente e algo para importar. */
    public function canCommit(ImportBatch $batch): bool
    {
        $files = $batch->files()->get();

        return $files->isNotEmpty()
            && $files->every(fn (ImportFile $f) => in_array($f->status, [ImportFile::DONE, ImportFile::PARTIAL, ImportFile::FAILED], true))
            && $files->contains(fn (ImportFile $f) => $f->status !== ImportFile::FAILED)
            && ! $files->contains(fn (ImportFile $f) => $this->needsChoice($f));
    }

    public function needsChoice(ImportFile $file): bool
    {
        return $file->status !== ImportFile::FAILED && $file->existing_conversation_id !== null && $file->choice === null;
    }

    /**
     * Candidatos para "Este é o seu nome nas mensagens?".
     *
     * @return array{ask: bool, candidates: list<array{name: string, files: int, messages: int, suggested: bool}>}
     */
    public function nameCandidates(ImportBatch $batch): array
    {
        $files = $batch->files()->whereIn('status', [ImportFile::DONE, ImportFile::PARTIAL])->whereNotNull('conversation_id')->get();
        $userNames = array_map(TextNormalizer::normalize(...), Setting::userNames());

        $rows = DB::table('participants')
            ->whereIn('conversation_id', $files->pluck('conversation_id'))
            ->get(['conversation_id', 'name', 'name_normalized', 'message_count']);

        if ($rows->contains(fn ($row) => in_array($row->name_normalized, $userNames, true))) {
            return ['ask' => false, 'candidates' => []];
        }

        $candidates = $rows->groupBy('name_normalized')
            ->map(fn ($group) => [
                'name' => $group->countBy('name')->sortDesc()->keys()->first(),
                'files' => $group->pluck('conversation_id')->unique()->count(),
                'messages' => (int) $group->sum('message_count'),
                'suggested' => false,
            ])
            ->sort(fn ($a, $b) => [$b['files'], $b['messages']] <=> [$a['files'], $a['messages']])
            ->values()
            ->all();

        if ($candidates !== []) {
            $first = $candidates[0];
            $second = $candidates[1]['files'] ?? 0;
            $candidates[0]['suggested'] = $first['files'] >= 2 && $first['files'] > $files->count() / 2 && $first['files'] > $second;
        }

        return ['ask' => $candidates !== [], 'candidates' => $candidates];
    }

    /**
     * Concluir: publica os rascunhos, somando à conversa existente ou mantendo separada.
     *
     * @return array{new: int, updated: int}
     */
    public function commit(ImportBatch $batch, ?string $nameChoice = null): array
    {
        if ($nameChoice !== null && trim($nameChoice) !== '') {
            $this->addUserName(trim($nameChoice));
        }

        $result = ['new' => 0, 'updated' => 0];
        $hashes = [];

        $files = $batch->files()->whereIn('status', [ImportFile::DONE, ImportFile::PARTIAL])->orderBy('id')->get();

        foreach ($files as $file) {
            if ($file->conversation_id === null) {
                continue;
            }

            DB::transaction(function () use ($file, &$result, &$hashes) {
                $draft = Conversation::query()->findOrFail($file->conversation_id);
                $existing = $this->mergeTarget($file, $draft);

                if ($existing !== null) {
                    array_push($hashes, ...$this->merge($draft, $existing));
                    $file->update(['conversation_id' => $existing->id]);
                    $result['updated']++;
                } else {
                    $draft->update(['status' => Conversation::ACTIVE, 'name' => $this->uniqueName($draft)]);
                    $result['new']++;
                }
            });
        }

        (new MediaStore($this->root))->deleteUnused($hashes);

        if (! (bool) Setting::get('keep_originals', true)) {
            foreach ($batch->files()->where('ext', 'zip')->pluck('stored_path')->unique() as $path) {
                @unlink($this->root.'/'.$path);
            }
        }

        $batch->update(['status' => ImportBatch::COMMITTED, 'committed_at' => now()]);

        return $result;
    }

    /**
     * Cancelar: apaga rascunhos, mídia que ninguém mais usa e os originais do lote.
     */
    public function cancel(ImportBatch $batch): void
    {
        $draftIds = Conversation::query()
            ->where('status', Conversation::DRAFT)
            ->whereIn('id', $batch->files()->whereNotNull('conversation_id')->pluck('conversation_id'))
            ->pluck('id');

        $hashes = MediaStore::hashesForMessages(fn ($q) => $q->whereIn('messages.conversation_id', $draftIds));

        DB::transaction(function () use ($batch, $draftIds) {
            Conversation::query()->whereIn('id', $draftIds)->delete();
            $batch->files()->delete();
            $batch->update(['status' => ImportBatch::CANCELLED]);
        });

        (new MediaStore($this->root))->deleteUnused($hashes);
        File::deleteDirectory($this->root."/originals/{$batch->id}");
    }

    public function addUserName(string $name): void
    {
        $names = Setting::userNames();
        $normalized = array_map(TextNormalizer::normalize(...), $names);

        if (! in_array(TextNormalizer::normalize($name), $normalized, true)) {
            $names[] = $name;
            Setting::put('user_names', $names);
        }

        $this->refreshIsMe();
    }

    /** Recalcula is_me em todas as conversas a partir dos nomes em Configurações. */
    public function refreshIsMe(): void
    {
        $normalized = array_map(TextNormalizer::normalize(...), Setting::userNames());

        DB::table('participants')->update(['is_me' => false]);

        if ($normalized !== []) {
            DB::table('participants')->whereIn('name_normalized', $normalized)->update(['is_me' => true]);
        }
    }

    /**
     * Conversa ativa onde o rascunho deve ser somado, ou nulo para manter separada.
     * Sem escolha registrada, soma quando outro arquivo deste lote já publicou o mesmo nome.
     */
    private function mergeTarget(ImportFile $file, Conversation $draft): ?Conversation
    {
        if ($file->choice === ImportFile::SEPARATE) {
            return null;
        }

        $byName = fn () => Conversation::query()->active()->where('name_normalized', $draft->name_normalized)->orderBy('id')->first();

        if ($file->choice === ImportFile::MERGE) {
            return Conversation::query()->active()->find($file->existing_conversation_id) ?? $byName();
        }

        return $file->existing_conversation_id === null ? $byName() : null;
    }

    /**
     * Passa as mensagens do rascunho para a conversa existente; as que já existem são descartadas.
     *
     * @return list<string> hashes de mídia que podem ter ficado sem uso
     */
    private function merge(Conversation $draft, Conversation $existing): array
    {
        foreach (DB::table('participants')->where('conversation_id', $draft->id)->get() as $participant) {
            DB::table('participants')->insertOrIgnore([
                'conversation_id' => $existing->id,
                'name' => $participant->name,
                'name_normalized' => $participant->name_normalized,
                'is_me' => $participant->is_me,
                'message_count' => 0,
            ]);

            $targetId = DB::table('participants')
                ->where('conversation_id', $existing->id)
                ->where('name_normalized', $participant->name_normalized)
                ->value('id');

            DB::table('messages')
                ->where('conversation_id', $draft->id)
                ->where('participant_id', $participant->id)
                ->update(['participant_id' => $targetId]);
        }

        // Mensagem que estava sem mídia na conversa existente ganha o anexo trazido agora.
        $upgrades = DB::table('messages AS d')
            ->join('messages AS e', function ($join) use ($existing) {
                $join->on('e.dedupe_key', '=', 'd.dedupe_key')->where('e.conversation_id', '=', $existing->id);
            })
            ->join('attachments AS a', 'a.message_id', '=', 'd.id')
            ->where('d.conversation_id', $draft->id)
            ->where('e.kind', 'missing')
            ->whereNotExists(fn ($q) => $q->from('attachments AS x')->whereColumn('x.message_id', 'e.id'))
            ->get(['d.id AS draft_id', 'e.id AS existing_id', 'd.kind', 'd.caption', 'd.search_text']);

        foreach ($upgrades as $upgrade) {
            DB::table('messages')->where('id', $upgrade->existing_id)->update([
                'kind' => $upgrade->kind,
                'caption' => $upgrade->caption,
                'search_text' => $upgrade->search_text,
            ]);
            DB::table('attachments')->where('message_id', $upgrade->draft_id)->update(['message_id' => $upgrade->existing_id]);
        }

        DB::update('UPDATE OR IGNORE messages SET conversation_id = ? WHERE conversation_id = ?', [$existing->id, $draft->id]);

        $hashes = MediaStore::hashesForMessages(fn ($q) => $q->where('messages.conversation_id', $draft->id));

        DB::table('messages')->where('conversation_id', $draft->id)->delete();
        $draft->delete();

        if ($draft->is_group && ! $existing->is_group) {
            $existing->update(['is_group' => true]);
        }

        ConversationStats::refresh($existing->id);

        return $hashes;
    }

    /** "Nome (2)", "Nome (3)"... quando já existe conversa ativa com o mesmo nome. */
    private function uniqueName(Conversation $draft): string
    {
        $name = $draft->name;
        $n = 1;

        while (Conversation::query()->active()->whereKeyNot($draft->id)->where('name_normalized', TextNormalizer::normalize($name))->exists()) {
            $n++;
            $name = "{$draft->name} ({$n})";
        }

        if ($name !== $draft->name) {
            $draft->name_normalized = TextNormalizer::normalize($name);
        }

        return $name;
    }

    private function safeName(string $name): string
    {
        $safe = preg_replace('~[^\pL\pN._ -]+~u', '_', $name) ?? 'arquivo';

        return mb_substr($safe, -120);
    }
}
