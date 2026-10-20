<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Import\ImportException;
use App\Import\ImportService;
use App\Jobs\AnalyzeImportFile;
use App\Models\ImportBatch;
use App\Models\ImportFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ImportController extends Controller
{
    public function __construct(private readonly ImportService $service) {}

    public function startUpload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $file = $this->service->startUpload($this->service->openOrCreateBatch(), $data['name'], (int) $data['size']);
        } catch (ImportException $e) {
            return $this->error($e->getMessage());
        }

        return response()->json(['upload_id' => $file->id, 'chunk_size' => ImportService::chunkSize()], 201);
    }

    public function uploadChunk(Request $request, ImportFile $upload): Response|JsonResponse
    {
        $request->validate(['index' => ['required', 'integer', 'min:0']]);

        try {
            $this->service->writeChunk($this->inOpenBatch($upload), (int) $request->query('index', $request->input('index')), $request->getContent(true));
        } catch (ImportException $e) {
            return $this->error($e->getMessage());
        }

        return response()->noContent();
    }

    public function completeUpload(ImportFile $upload): JsonResponse
    {
        try {
            $file = $this->service->completeUpload($this->inOpenBatch($upload));
        } catch (ImportException $e) {
            return $this->error($e->getMessage());
        }

        AnalyzeImportFile::dispatch($file->id);

        return response()->json($this->file($file->refresh()), 201);
    }

    /** Lote atual com o estado de cada arquivo, a pergunta do nome e se já dá para concluir. */
    public function index(): JsonResponse
    {
        $batch = $this->service->openBatch();

        if ($batch === null) {
            return response()->json(['batch_id' => null, 'files' => [], 'name_question' => null, 'can_commit' => false, 'hint' => '']);
        }

        $files = $batch->files()->with('existingConversation', 'conversation')->orderBy('id')->get();
        $allDone = $files->isNotEmpty() && $files->every(fn (ImportFile $f) => in_array($f->status, [ImportFile::DONE, ImportFile::PARTIAL, ImportFile::FAILED], true));
        $pending = $files->first(fn (ImportFile $f) => $this->service->needsChoice($f));

        return response()->json([
            'batch_id' => $batch->id,
            'files' => $files->map(fn (ImportFile $f) => $this->file($f))->values(),
            'name_question' => $allDone ? $this->nameQuestion($batch, $files->count()) : null,
            'can_commit' => $this->service->canCommit($batch),
            'hint' => match (true) {
                $files->isEmpty() => '',
                ! $allDone => 'Aguarde o processamento dos arquivos',
                $pending !== null => 'Escolha o que fazer com “'.($pending->conversation?->name ?? $pending->original_name).'”',
                ! $files->contains(fn (ImportFile $f) => $f->status !== ImportFile::FAILED) => 'Nenhum arquivo pôde ser importado',
                default => 'Tudo pronto para concluir',
            },
        ]);
    }

    public function updateFile(Request $request, ImportFile $file): JsonResponse
    {
        $data = $request->validate(['choice' => ['required', Rule::in([ImportFile::MERGE, ImportFile::SEPARATE])]]);
        $file = $this->inOpenBatch($file);

        abort_if($file->existing_conversation_id === null, 422, 'Não há conversa existente para este arquivo.');

        $file->update(['choice' => $data['choice']]);

        return response()->json($this->file($file->refresh()));
    }

    public function commit(Request $request): JsonResponse
    {
        $data = $request->validate(['name_choice' => ['nullable', 'string', 'max:120']]);
        $batch = $this->service->openBatch();

        if ($batch === null || ! $this->service->canCommit($batch)) {
            return $this->error('Ainda não é possível concluir esta importação.');
        }

        return response()->json($this->service->commit($batch, $data['name_choice'] ?? null));
    }

    public function cancel(): Response
    {
        if (($batch = $this->service->openBatch()) !== null) {
            $this->service->cancel($batch);
        }

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function file(ImportFile $f): array
    {
        return [
            'id' => $f->id,
            'name' => $f->original_name,
            'ext' => $f->ext,
            'size_bytes' => $f->size_bytes,
            'status' => $f->status,
            'percent' => $f->percent,
            'conversation_name' => $f->conversation?->name,
            'stats' => $f->stats,
            'unrecognized_count' => $f->unrecognized_count,
            'unrecognized_lines' => $f->unrecognized_lines ?? [],
            'error_message' => $f->error_message,
            'existing' => $f->existing_conversation_id === null || $f->status === ImportFile::FAILED ? null : [
                'id' => $f->existing_conversation_id,
                'name' => $f->existingConversation?->name,
            ],
            'choice' => $f->choice,
            'needs_choice' => $this->service->needsChoice($f),
        ];
    }

    /** @return array{ask: bool, candidates: list<array<string, mixed>>} */
    private function nameQuestion(ImportBatch $batch, int $totalFiles): array
    {
        $question = $this->service->nameCandidates($batch);

        $question['candidates'] = array_map(fn ($c) => [
            'name' => $c['name'],
            'hint' => sprintf(
                'Autor de %d %s %s %d %s',
                $c['messages'], $c['messages'] === 1 ? 'mensagem' : 'mensagens',
                $c['files'] === $totalFiles && $totalFiles > 1 ? 'nos' : 'em',
                $c['files'], $c['files'] === 1 ? 'arquivo' : 'arquivos',
            ),
            'suggested' => $c['suggested'],
        ], $question['candidates']);

        return $question;
    }

    private function inOpenBatch(ImportFile $file): ImportFile
    {
        abort_unless($file->batch?->status === ImportBatch::OPEN, 404);

        return $file;
    }

    private function error(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 422);
    }
}
