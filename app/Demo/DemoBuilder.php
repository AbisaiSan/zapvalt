<?php

namespace App\Demo;

use App\Import\MessageClassifier;
use App\Models\Message;
use Carbon\CarbonImmutable;
use ZipArchive;

/**
 * Gera exports fictícios no formato do WhatsApp (Android, 24 horas), prontos para o importador.
 */
class DemoBuilder
{
    /** Dias somados às datas do protótipo para que o "hoje" dele vire o dia atual. */
    private int $shiftDays;

    public function __construct(private readonly string $dir, ?CarbonImmutable $today = null)
    {
        $today ??= CarbonImmutable::today();
        $this->shiftDays = (int) CarbonImmutable::parse(DemoData::PROTOTYPE_TODAY)->diffInDays($today, false);

        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
    }

    /**
     * Monta o .zip de uma conversa do protótipo, com texto e mídia.
     *
     * @param  array{id: string, name: string, parts: list<string>, file: string, av: int, rows: list<array>}  $conversation
     * @return array{path: string, name: string, durations: array<string, int>, pages: array<string, int>}
     */
    public function prototypeZip(array $conversation): array
    {
        $base = preg_replace('~\.(zip|txt)$~', '', $conversation['file']);
        $zipPath = $this->dir.'/'.$conversation['id'].'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $lines = [];
        $durations = [];
        $pages = [];
        $audioSeq = 0;

        foreach ($conversation['rows'] as [$day, $time, $who, $content]) {
            $date = CarbonImmutable::parse('2026-'.$day)->addDays($this->shiftDays);
            $prefix = $date->format('d/m/Y').' '.($time ?? '00:00').' - ';
            $sender = match (true) {
                $who === 'me' => DemoData::ME,
                $who === 's' => null,
                default => $conversation['parts'][$who],
            };

            $body = $this->body($content, $date, $zip, $audioSeq, $durations, $pages);
            $lines[] = $prefix.($sender === null ? '' : $sender.': ').$body;
        }

        $zip->addFromString($base.'.txt', implode("\n", $lines)."\n");
        $zip->close();

        return ['path' => $zipPath, 'name' => $base.'.zip', 'durations' => $durations, 'pages' => $pages];
    }

    /**
     * Conversa sintética grande (só texto), para medir desempenho.
     */
    public function syntheticText(string $name, array $people, int $messages, CarbonImmutable $start, CarbonImmutable $end, int $seed): string
    {
        mt_srand($seed);
        $path = $this->dir.'/sintetica-'.$seed.'.txt';
        $out = fopen($path, 'wb');
        $span = max(60, $end->diffInMinutes($start, true));
        $step = $span / max(1, $messages);
        $minute = 0.0;

        for ($i = 0; $i < $messages; $i++) {
            $minute += $step * (mt_rand(20, 180) / 100);
            $at = $start->addMinutes((int) min($minute, $span));
            $sender = mt_rand(0, 2) === 0 ? DemoData::ME : $people[mt_rand(0, count($people) - 1)];
            fwrite($out, $at->format('d/m/Y H:i').' - '.$sender.': '.self::sentence()."\n");
        }

        fclose($out);

        return $path;
    }

    private static function sentence(): string
    {
        static $words = ['proposta', 'orçamento', 'reunião', 'contrato', 'entrega', 'amanhã', 'ação', 'valores', 'evento',
            'confirmado', 'obrigado', 'pagamento', 'nota fiscal', 'café', 'cadeiras', 'cliente', 'prazo', 'agenda',
            'fornecedor', 'transporte', 'layout', 'aprovado', 'revisão', 'semana', 'hoje', 'combinado', 'pedido', 'boleto'];
        static $starts = ['Bom dia,', 'Oi,', 'Certo,', 'Perfeito,', 'Pessoal,', 'Sobre o', 'Confirmo o', 'Preciso do', 'Segue o'];

        $n = mt_rand(2, 12);
        $text = $starts[mt_rand(0, count($starts) - 1)];
        for ($i = 0; $i < $n; $i++) {
            $text .= ' '.$words[mt_rand(0, count($words) - 1)];
        }

        return $text.'.';
    }

    /**
     * @param  array<string, int>  $durations
     * @param  array<string, int>  $pages
     */
    private function body(string|array $content, CarbonImmutable $date, ZipArchive $zip, int &$audioSeq, array &$durations, array &$pages): string
    {
        if (is_string($content)) {
            return $content;
        }

        $caption = isset($content['caption']) ? "\n".$content['caption'] : '';

        switch ($content['k']) {
            case 'sys':
            case 'call':
                return $content['text'];
            case 'deleted':
                return 'Essa mensagem foi apagada';
            case 'missing':
                return '<Mídia oculta>';
            case 'audio':
                $file = sprintf('PTT-%s-WA%04d.wav', $date->format('Ymd'), ++$audioSeq);
                $zip->addFromString($file, FakeMedia::audio($content['dur']));
                $durations[$file] = $content['dur'];

                return "{$file} (arquivo anexado)";
        }

        $file = $content['file'];

        match (MessageClassifier::fileKind($file)) {
            Message::IMAGE => $zip->addFromString($file, ($content['broken'] ?? false)
                ? 'arquivo corrompido de demonstração'
                : FakeMedia::image($content['hue'], $content['ratio'] ?? '4/3', $file)),
            Message::STICKER => $zip->addFromString($file, FakeMedia::sticker($content['hue'])),
            Message::VIDEO => $this->addVideo($zip, $file, $content),
            default => $zip->addFromString($file, str_ends_with(strtolower($file), '.pdf')
                ? FakeMedia::pdf($content['pages'] ?? 1, $file, FakeMedia::bytes($content['size'] ?? '10 KB'))
                : FakeMedia::document($file, FakeMedia::bytes($content['size'] ?? '10 KB'))),
        };

        if (isset($content['dur'])) {
            $durations[$file] = $content['dur'];
        }
        if (isset($content['pages'])) {
            $pages[$file] = $content['pages'];
        }

        return "{$file} (arquivo anexado){$caption}";
    }

    private function addVideo(ZipArchive $zip, string $file, array $content): bool
    {
        $temp = $this->dir.'/'.$file;
        FakeMedia::video($content['hue'], $content['dur'], $temp);

        return $zip->addFile($temp, $file);
    }
}
