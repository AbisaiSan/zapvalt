<?php

namespace App\Console\Commands;

use App\Import\ImportAnalyzer;
use App\Import\ImportException;
use App\Import\ImportService;
use App\Models\ImportFile;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class ImportCommand extends Command
{
    protected $signature = 'zapvault:import
        {arquivos* : Arquivos .txt ou .zip exportados do WhatsApp}
        {--commit : Concluir a importação sem perguntas (soma às conversas existentes, salvo --separate)}
        {--merge : Somar às conversas que já existem com o mesmo nome (padrão)}
        {--separate : Manter separadas as conversas que já existem com o mesmo nome}
        {--me= : Seu nome nas mensagens, se ainda não estiver nas Configurações}';

    protected $description = 'Importa conversas exportadas (.txt ou .zip) sem usar o navegador';

    public function handle(ImportService $service, ImportAnalyzer $analyzer): int
    {
        $open = $service->openBatch();

        if ($open !== null && $open->files()->exists()) {
            $this->error('Há uma importação em andamento na tela Importar. Conclua ou cancele antes de importar pelo terminal.');

            return self::FAILURE;
        }

        $batch = $service->openOrCreateBatch();
        $files = [];

        foreach ($this->argument('arquivos') as $path) {
            try {
                $files[] = $service->addFile($batch, $path);
            } catch (ImportException $e) {
                $this->error(basename($path).': '.$e->getMessage());
            }
        }

        if ($files === []) {
            $service->cancel($batch);

            return self::FAILURE;
        }

        foreach ($files as $file) {
            $this->line("Analisando {$file->original_name}…");
            $analyzer->analyze($file);
        }

        $cards = $batch->files()->orderBy('id')->get();
        $this->printCards($cards);

        foreach ($cards->filter(fn (ImportFile $f) => $service->needsChoice($f)) as $file) {
            $file->update(['choice' => $this->choiceFor($file)]);
        }

        $failed = $cards->contains(fn (ImportFile $f) => $f->status === ImportFile::FAILED);

        if (! $service->canCommit($batch->refresh())) {
            $service->cancel($batch);
            $this->error('Nenhuma conversa foi reconhecida. Nada foi importado.');

            return self::FAILURE;
        }

        $nameChoice = $this->nameChoice($service, $batch);

        $commit = $this->option('commit')
            || ($this->input->isInteractive() && confirm('Concluir a importação?', default: true));

        if (! $commit) {
            $service->cancel($batch);
            $this->warn('Importação não concluída. Nada foi gravado no acervo. Use --commit para concluir.');

            return self::SUCCESS;
        }

        $result = $service->commit($batch, $nameChoice);
        $this->info("Importação concluída: {$result['new']} conversa(s) nova(s), {$result['updated']} atualizada(s).");

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  iterable<ImportFile>  $cards
     */
    private function printCards(iterable $cards): void
    {
        $rows = [];

        foreach ($cards as $file) {
            $stats = $file->stats ?? [];
            $rows[] = [
                $file->original_name,
                match ($file->status) {
                    ImportFile::DONE => 'ok',
                    ImportFile::PARTIAL => 'com linhas não reconhecidas',
                    ImportFile::FAILED => 'falhou',
                    default => $file->status,
                },
                $stats['new'] ?? '-',
                $stats['repeated'] ?? '-',
                $stats['media_found'] ?? '-',
                $stats['media_missing'] ?? '-',
            ];
        }

        $this->table(['Arquivo', 'Situação', 'Novas', 'Repetidas', 'Anexos encontrados', 'Anexos faltantes'], $rows);

        foreach ($cards as $file) {
            if ($file->status === ImportFile::FAILED) {
                $this->error("{$file->original_name}: {$file->error_message}");
            }

            if ($file->unrecognized_count > 0) {
                $lines = collect($file->unrecognized_lines)->pluck('line')->take(20)->implode(', ');
                $this->warn("{$file->original_name}: {$file->unrecognized_count} linha(s) não reconhecida(s) (linhas {$lines}).");
            }
        }
    }

    private function choiceFor(ImportFile $file): string
    {
        if ($this->option('separate')) {
            return ImportFile::SEPARATE;
        }

        if ($this->option('merge') || $this->option('commit') || ! $this->input->isInteractive()) {
            return ImportFile::MERGE;
        }

        $name = $file->existingConversation?->name ?? $file->original_name;

        return select(
            label: "Já existe uma conversa com {$name} no acervo.",
            options: [ImportFile::MERGE => 'Somar à existente', ImportFile::SEPARATE => 'Manter separada'],
            default: ImportFile::MERGE,
        );
    }

    private function nameChoice(ImportService $service, $batch): ?string
    {
        if ($this->option('me')) {
            return (string) $this->option('me');
        }

        $question = $service->nameCandidates($batch);

        if (! $question['ask'] || $this->option('commit') || ! $this->input->isInteractive()) {
            return null;
        }

        $options = [];
        foreach ($question['candidates'] as $i => $candidate) {
            $label = "{$candidate['name']} (autor de {$candidate['messages']} mensagens em {$candidate['files']} arquivos)";
            $options["c{$i}"] = $candidate['suggested'] ? "{$label} · Sugerido" : $label;
        }
        $options['none'] = 'Nenhum destes';

        $default = ($question['candidates'][0]['suggested'] ?? false) ? 'c0' : 'none';
        $picked = select(label: 'Este é o seu nome nas mensagens?', options: $options, default: $default);

        return $picked === 'none' ? null : $question['candidates'][(int) substr($picked, 1)]['name'];
    }
}
