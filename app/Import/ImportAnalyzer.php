<?php

namespace App\Import;

use App\Import\Media\MediaStore;
use App\Models\Conversation;
use App\Models\ImportFile;
use App\Models\Message;
use App\Models\Setting;
use App\Support\TextNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fase 1 da importação: lê o arquivo e grava cada conversa como rascunho, com as contagens do cartão.
 */
class ImportAnalyzer
{
    public const NOT_RECOGNIZED = 'Não foi possível reconhecer este arquivo como conversa.';

    private const CHUNK = 1000;

    private MessageClassifier $classifier;

    private MediaStore $media;

    private string $root;

    public function __construct()
    {
        $this->classifier = new MessageClassifier;
        $this->root = config('zapvault.data_path');
        $this->media = new MediaStore($this->root);
    }

    public function analyze(ImportFile $file): void
    {
        $file->update(['status' => ImportFile::PROCESSING, 'percent' => 0, 'started_at' => now(), 'error_message' => null]);

        $reader = new SourceReader($this->root.'/uploads-tmp');

        try {
            $sources = $reader->read($this->root.'/'.$file->stored_path, $file->original_name);

            $recognized = [];
            foreach ($sources as $source) {
                $parser = new ChatParser($source->open);
                if ($parser->inspect()) {
                    $recognized[] = [$source, $parser];
                }
            }

            if ($recognized === []) {
                $this->fail($file, self::NOT_RECOGNIZED);

                return;
            }

            foreach ($recognized as $i => [$source, $parser]) {
                $target = $i === 0 ? $file : $this->sibling($file, $source);

                if ($i === 0 && count($recognized) > 1) {
                    $file->update(['original_name' => $source->label]);
                }

                $this->analyzeSource($target, $source, $parser);
            }
        } catch (ImportException $e) {
            $this->fail($file, $e->getMessage());
        } catch (Throwable $e) {
            // Sem conteúdo de mensagens no log: só o tipo do erro e onde ocorreu.
            Log::error('Falha ao analisar importação', [
                'import_file_id' => $file->id,
                'error' => $e::class,
                'at' => basename($e->getFile()).':'.$e->getLine(),
            ]);
            $this->fail($file, 'Erro inesperado ao ler o arquivo. Tente importar de novo.');
        } finally {
            $reader->close();
        }
    }

    private function analyzeSource(ImportFile $file, ChatSource $source, ChatParser $parser): void
    {
        $name = $source->conversationName;
        $normalizedName = TextNormalizer::normalize($name);

        $draft = Conversation::query()->create([
            'name' => $name,
            'name_normalized' => $normalizedName,
            'phone' => self::looksLikePhone($name) ? $name : null,
            'avatar_index' => crc32($normalizedName) % 8,
            'status' => Conversation::DRAFT,
            'source_file_name' => $source->label,
        ]);

        $existing = Conversation::query()->active()->where('name_normalized', $normalizedName)->orderBy('id')->first();

        $file->update([
            'conversation_id' => $draft->id,
            'existing_conversation_id' => $existing?->id,
            'detected_format' => $parser->format,
        ]);

        $userNames = array_map(TextNormalizer::normalize(...), Setting::userNames());
        $state = new AnalysisState($draft->id, $file->id, $userNames);
        $buffer = [];
        $lastPercent = -1;
        $size = max(1, $source->size);

        $progress = function (int $bytes) use ($file, $size, &$lastPercent) {
            $percent = min(95, (int) floor($bytes / $size * 95));
            if ($percent !== $lastPercent) {
                $lastPercent = $percent;
                $file->newQuery()->whereKey($file->id)->update(['percent' => $percent]);
            }
        };

        foreach ($parser->messages($progress) as $raw) {
            $message = $this->classifier->classify($raw, $source->media);
            $buffer[] = $message;
            $state->observe($message);

            if (count($buffer) >= self::CHUNK) {
                $this->flush($buffer, $state, $source);
                $buffer = [];
            }
        }

        $this->flush($buffer, $state, $source);

        if ($state->isGroup()) {
            $draft->update(['is_group' => true]);
        }

        ConversationStats::refresh($draft->id);

        $total = DB::table('messages')->where('conversation_id', $draft->id)->count();
        $repeated = $existing === null ? 0 : DB::table('messages AS d')
            ->where('d.conversation_id', $draft->id)
            ->whereExists(fn ($q) => $q->from('messages AS e')
                ->whereColumn('e.dedupe_key', 'd.dedupe_key')
                ->where('e.conversation_id', $existing->id))
            ->count();

        $file->update([
            'status' => $parser->unrecognizedCount > 0 ? ImportFile::PARTIAL : ImportFile::DONE,
            'percent' => 100,
            'stats' => [
                'new' => $total - $repeated,
                'repeated' => $repeated,
                'media_found' => $state->mediaFound,
                'media_missing' => $state->mediaMissing,
                'lines_read' => $parser->linesRead,
            ],
            'unrecognized_lines' => $parser->unrecognized,
            'unrecognized_count' => $parser->unrecognizedCount,
            'finished_at' => now(),
        ]);
    }

    /**
     * Grava um lote de mensagens com INSERT OR IGNORE e, depois, os anexos.
     *
     * @param  list<ClassifiedMessage>  $messages
     */
    private function flush(array $messages, AnalysisState $state, ChatSource $source): void
    {
        if ($messages === []) {
            return;
        }

        DB::transaction(function () use ($messages, $state, $source) {
            $rows = [];
            $withFile = [];

            foreach ($messages as $message) {
                $key = $state->dedupeKey($message);

                $rows[] = [
                    'conversation_id' => $state->conversationId,
                    'participant_id' => $message->sender === null ? null : $state->participantId($message->sender),
                    'sent_at' => $message->sentAt,
                    'kind' => $message->kind,
                    'body' => $message->body,
                    'caption' => $message->caption,
                    'system_icon' => $message->systemIcon,
                    'search_text' => Message::searchTextFor($message->kind, $message->body, $message->caption),
                    'dedupe_key' => $key,
                    'source_line' => $message->line,
                    'import_file_id' => $state->importFileId,
                ];

                if ($message->kind === Message::MISSING) {
                    $state->mediaMissing++;
                } elseif ($message->fileName !== null) {
                    $withFile[$key] = $message;
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('messages')->insertOrIgnore($chunk);
            }

            if ($withFile === []) {
                return;
            }

            $ids = DB::table('messages')
                ->where('conversation_id', $state->conversationId)
                ->whereIn('dedupe_key', array_keys($withFile))
                ->whereNotExists(fn ($q) => $q->from('attachments')->whereColumn('attachments.message_id', 'messages.id'))
                ->pluck('id', 'dedupe_key');

            foreach ($withFile as $key => $message) {
                if (! isset($ids[$key])) {
                    continue;
                }

                $this->attach((int) $ids[$key], $message, $state, $source);
            }
        });
    }

    private function attach(int $messageId, ClassifiedMessage $message, AnalysisState $state, ChatSource $source): void
    {
        try {
            $stored = $state->stored[$message->fileName] ??= $this->media->store($source->media->open($message->fileName), $message->fileName);
            $info = $state->described[$stored['sha256']] ??= $this->media->describe($message->kind, $stored['path'], $stored['sha256'], $stored['ext']);
        } catch (Throwable) {
            DB::table('messages')->where('id', $messageId)->update(['kind' => Message::MISSING, 'search_text' => null]);
            $state->mediaMissing++;

            return;
        }

        DB::table('attachments')->insert([
            'message_id' => $messageId,
            'original_name' => basename($message->fileName),
            'ext' => $stored['ext'],
            'mime' => MediaStore::mime($stored['ext']),
            'size_bytes' => $stored['size'],
            'sha256' => $stored['sha256'],
            'storage_path' => $stored['path'],
            'thumb_path' => $info['thumb_path'],
            'width' => $info['width'],
            'height' => $info['height'],
            'duration_seconds' => $info['duration_seconds'],
            'pages' => $info['pages'],
        ]);

        $state->mediaFound++;
    }

    private function sibling(ImportFile $file, ChatSource $source): ImportFile
    {
        return ImportFile::query()->create([
            'batch_id' => $file->batch_id,
            'original_name' => $source->label,
            'stored_path' => $file->stored_path,
            'ext' => $file->ext,
            'size_bytes' => $source->size,
            'sha256' => $file->sha256,
            'status' => ImportFile::PROCESSING,
            'started_at' => now(),
        ]);
    }

    private function fail(ImportFile $file, string $message): void
    {
        $file->update([
            'status' => ImportFile::FAILED,
            'percent' => 100,
            'error_message' => $message,
            'finished_at' => now(),
        ]);
    }

    public static function looksLikePhone(string $name): bool
    {
        return preg_match('~^\+?[\d\s().-]{8,}$~', $name) === 1 && preg_match_all('~\d~', $name) >= 8;
    }
}
