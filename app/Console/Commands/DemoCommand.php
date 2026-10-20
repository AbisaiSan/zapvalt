<?php

namespace App\Console\Commands;

use App\Demo\DemoBuilder;
use App\Demo\DemoData;
use App\Import\ImportAnalyzer;
use App\Import\ImportService;
use App\Models\Conversation;
use App\Models\ImportFile;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;

class DemoCommand extends Command
{
    protected $signature = 'zapvault:demo
        {--scale= : Use "large" para gerar cerca de 300 conversas e 1,5 milhão de mensagens}
        {--force : Rodar mesmo que o acervo já tenha conversas}';

    protected $description = 'Cria as conversas fictícias do protótipo (dados de demonstração)';

    /** Conversas sintéticas da escala grande: a primeira tem 100 mil mensagens. */
    private const LARGE_CONVERSATIONS = 288;

    private const LARGE_MESSAGES = 1_500_000;

    private const LARGE_BIGGEST = 100_000;

    public function handle(ImportService $service, ImportAnalyzer $analyzer): int
    {
        $large = $this->option('scale') === 'large';

        if ($this->option('scale') !== null && ! $large) {
            $this->error('Escala desconhecida. Use --scale=large ou omita a opção.');

            return self::FAILURE;
        }

        $open = $service->openBatch();
        if ($open !== null && $open->files()->exists()) {
            $this->error('Há uma importação em andamento na tela Importar. Conclua ou cancele antes.');

            return self::FAILURE;
        }

        $existing = Conversation::query()->active()->count();
        if ($existing > 0 && ! $this->option('force')) {
            $question = "O acervo já tem {$existing} conversa(s). Os dados fictícios serão misturados a elas. Continuar?";

            if (! $this->input->isInteractive() || ! confirm($question, default: false)) {
                $this->error('Nada foi feito. Para misturar dados fictícios a um acervo com conversas, use --force.');

                return self::FAILURE;
            }
        }

        $root = config('zapvault.data_path');
        $dir = $root.'/uploads-tmp/demo-'.Str::random(8);
        $builder = new DemoBuilder($dir);

        try {
            $service->addUserName(DemoData::ME);
            $batch = $service->openOrCreateBatch();

            $this->line('Montando as conversas do protótipo…');
            $prototype = [];
            foreach (DemoData::conversations() as $conversation) {
                $zip = $builder->prototypeZip($conversation);
                $prototype[] = [$service->addFile($batch, $zip['path'], $zip['name']), $conversation, $zip];
            }

            if ($large) {
                $this->line('Gerando as conversas sintéticas grandes…');
                $this->addLarge($service, $batch, $builder);
            }

            $files = $batch->files()->orderBy('id')->get();
            $bar = $this->output->createProgressBar($files->count());
            foreach ($files as $file) {
                $analyzer->analyze($file);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();

            foreach ($batch->files()->get() as $file) {
                if ($service->needsChoice($file)) {
                    $file->update(['choice' => ImportFile::MERGE]);
                }
            }

            $failed = $batch->files()->where('status', ImportFile::FAILED)->count();
            $result = $service->commit($batch->refresh());

            foreach ($prototype as [$file, $conversation, $zip]) {
                $this->finishPrototype($file->refresh(), $conversation, $zip);
            }

            File::deleteDirectory($root."/originals/{$batch->id}");

            $this->info("Dados de demonstração prontos: {$result['new']} conversa(s) nova(s), {$result['updated']} atualizada(s).");
            $this->line(sprintf(
                'Acervo: %d conversas, %d mensagens, %d anexos.',
                Conversation::query()->active()->count(),
                (int) Conversation::query()->active()->sum('message_count'),
                DB::table('attachments')->count(),
            ));

            return $failed > 0 ? self::FAILURE : self::SUCCESS;
        } finally {
            File::deleteDirectory($dir);
        }
    }

    /**
     * Ajustes que o export não carrega: arquivo de origem, cor do avatar, duração e páginas.
     *
     * @param  array{file: string, av: int}  $conversation
     * @param  array{durations: array<string, int>, pages: array<string, int>}  $zip
     */
    private function finishPrototype(ImportFile $file, array $conversation, array $zip): void
    {
        if ($file->conversation_id === null) {
            return;
        }

        Conversation::query()->whereKey($file->conversation_id)->update([
            'source_file_name' => $conversation['file'],
            'avatar_index' => $conversation['av'],
        ]);

        $attachments = DB::table('attachments')
            ->join('messages', 'messages.id', '=', 'attachments.message_id')
            ->where('messages.conversation_id', $file->conversation_id);

        foreach ($zip['durations'] as $name => $seconds) {
            (clone $attachments)->where('attachments.original_name', $name)->update(['duration_seconds' => $seconds]);
        }

        foreach ($zip['pages'] as $name => $count) {
            (clone $attachments)->where('attachments.original_name', $name)->update(['pages' => $count]);
        }
    }

    private function addLarge(ImportService $service, $batch, DemoBuilder $builder): void
    {
        mt_srand(2026);
        $first = ['Adriana', 'Bruno', 'Camila', 'Daniel', 'Eduarda', 'Felipe', 'Gabriela', 'Henrique', 'Isabela', 'Júlio',
            'Larissa', 'Mateus', 'Natália', 'Otávio', 'Patrícia', 'Renato', 'Sabrina', 'Tiago', 'Vanessa', 'Wagner'];
        $last = ['Almeida', 'Barbosa', 'Cardoso', 'Duarte', 'Esteves', 'Farias', 'Gomes', 'Holanda', 'Ibrahim', 'Jardim',
            'Lopes', 'Macedo', 'Nogueira', 'Oliveira', 'Pacheco', 'Queiroz', 'Rezende', 'Siqueira', 'Toledo', 'Vieira'];

        $today = CarbonImmutable::today();
        $remaining = self::LARGE_MESSAGES - self::LARGE_BIGGEST;
        $average = intdiv($remaining, self::LARGE_CONVERSATIONS - 1);
        $bar = $this->output->createProgressBar(self::LARGE_CONVERSATIONS);

        for ($i = 0; $i < self::LARGE_CONVERSATIONS; $i++) {
            $isGroup = $i % 10 === 5;
            $people = [];
            foreach (range(1, $isGroup ? mt_rand(3, 6) : 1) as $ignored) {
                $people[] = $first[mt_rand(0, 19)].' '.$last[mt_rand(0, 19)];
            }
            $people = array_values(array_unique($people));

            $name = $isGroup ? "Grupo Equipe {$last[$i % 20]} ".($i + 1) : $people[0].' '.($i + 1);
            $count = match (true) {
                $i === 0 => self::LARGE_BIGGEST,
                $i === self::LARGE_CONVERSATIONS - 1 => max(100, $remaining),
                default => min($remaining, (int) round($average * mt_rand(40, 160) / 100)),
            };
            $remaining -= $i === 0 ? 0 : $count;

            $end = $today->subDays(mt_rand(0, 60))->setTime(mt_rand(8, 20), mt_rand(0, 59));
            $start = $end->subDays(mt_rand(60, 1000));
            $path = $builder->syntheticText($name, $people, $count, $start, $end, 1000 + $i);

            $service->addFile($batch, $path, "Conversa do WhatsApp com {$name}.txt");
            @unlink($path);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
