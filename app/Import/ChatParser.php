<?php

namespace App\Import;

use App\Models\ImportFile;
use App\Support\TextNormalizer;
use Closure;
use Generator;
use RuntimeException;

/**
 * Lê o texto de uma conversa exportada, linha a linha, sem carregar o arquivo inteiro.
 *
 * Formatos aceitos (nesta ordem de tentativa):
 *  - Android 24 horas:        19/10/2026 09:14 - Maria Souza: Bom dia!
 *  - Android com AM ou PM:    15/10/26, 7:50 PM - Eduardo Nunes: Confirmado
 *  - iPhone, com colchetes:   [19/10/2026 09:14:05] Maria Souza: Bom dia!
 */
class ChatParser
{
    public const ANDROID_24H = 'android_24h';

    public const ANDROID_AMPM = 'android_ampm';

    public const IOS = 'ios';

    private const PATTERNS = [
        self::ANDROID_24H => '~^(\d{1,2})/(\d{1,2})/(\d{2,4}),? (\d{1,2}):(\d{2}) - (.*)$~su',
        self::ANDROID_AMPM => '~^(\d{1,2})/(\d{1,2})/(\d{2,4}),? (\d{1,2}):(\d{2}) ?([AaPp])\.? ?[Mm]\.? - (.*)$~su',
        self::IOS => '~^\[(\d{1,2})/(\d{1,2})/(\d{2,4}),? (\d{1,2}):(\d{2}):(\d{2})(?: ?([AaPp])\.? ?[Mm]\.?)?\] (.*)$~su',
    ];

    /** Linha que começa como data, mas não casa com nenhum formato, é "não reconhecida". */
    private const DATE_LIKE = '~^\[?\d{1,2}/\d{1,2}/\d{2,4}~';

    /** Quantas linhas iniciais decidem se o texto é uma conversa. */
    private const SAMPLE_LINES = 300;

    private const MIN_MATCHES = 5;

    public ?string $format = null;

    public bool $dayFirst = true;

    public int $linesRead = 0;

    public int $unrecognizedCount = 0;

    /** @var list<array{line: int, text: string}> */
    public array $unrecognized = [];

    /**
     * @param  Closure(): resource  $open  abre (de novo) o texto da conversa
     */
    public function __construct(private readonly Closure $open) {}

    /**
     * Primeira passada: reconhece o formato e decide a ordem dia/mês do arquivo.
     */
    public function inspect(): bool
    {
        $counts = array_fill_keys(array_keys(self::PATTERNS), 0);
        $sampled = 0;
        $dayEvidence = false;
        $monthEvidence = false;

        foreach ($this->lines() as $n => $line) {
            if ($line === '') {
                continue;
            }

            $match = $this->match($line);

            if ($sampled < self::SAMPLE_LINES) {
                $sampled++;
                if ($match !== null) {
                    $counts[$match[0]]++;
                }
            }

            if ($match !== null) {
                $dayEvidence = $dayEvidence || (int) $match[1][1] > 12;
                $monthEvidence = $monthEvidence || (int) $match[1][2] > 12;
            }
        }

        $matches = array_sum($counts);
        $required = min(self::MIN_MATCHES, $sampled);

        if ($sampled === 0 || $matches < $required) {
            return false;
        }

        arsort($counts);
        $this->format = array_key_first($counts);
        $this->dayFirst = $dayEvidence || ! $monthEvidence;

        return true;
    }

    /**
     * Segunda passada: devolve as mensagens, juntando linhas de continuação.
     *
     * @param  (Closure(int $bytes): void)|null  $progress
     * @return Generator<int, RawMessage>
     */
    public function messages(?Closure $progress = null): Generator
    {
        if ($this->format === null) {
            throw new RuntimeException('Chame inspect() antes de messages().');
        }

        $this->linesRead = 0;
        $this->unrecognizedCount = 0;
        $this->unrecognized = [];

        /** @var RawMessage|null $current */
        $current = null;

        foreach ($this->lines($progress) as $n => $line) {
            $this->linesRead = $n;
            $match = $line === '' ? null : $this->match($line);

            if ($match !== null && ($message = $this->build($n, $match)) !== null) {
                if ($current !== null) {
                    yield $current;
                }
                $current = $message;

                continue;
            }

            if ($match !== null || preg_match(self::DATE_LIKE, $line)) {
                $this->addUnrecognized($n, $line);

                continue;
            }

            if ($current !== null) {
                $current->body .= "\n".TextNormalizer::clean($line);
            } elseif ($line !== '') {
                $this->addUnrecognized($n, $line);
            }
        }

        if ($current !== null) {
            yield $current;
        }
    }

    /**
     * @return array{0: string, 1: array<int, string>, 2: string}|null formato, grupos e linha original
     */
    private function match(string $raw): ?array
    {
        $line = TextNormalizer::clean($raw);

        foreach (self::PATTERNS as $format => $pattern) {
            if (preg_match($pattern, $line, $m)) {
                return [$format, $m, $raw];
            }
        }

        return null;
    }

    /**
     * @param  array{0: string, 1: array<int, string>, 2: string}  $match
     */
    private function build(int $line, array $match): ?RawMessage
    {
        [$format, $m, $raw] = $match;

        $a = (int) $m[1];
        $b = (int) $m[2];
        $year = (int) $m[3];
        $hour = (int) $m[4];
        $minute = (int) $m[5];
        $second = 0;
        $ampm = null;

        if ($format === self::ANDROID_AMPM) {
            $ampm = $m[6];
            $rest = $m[7];
        } elseif ($format === self::IOS) {
            $second = (int) $m[6];
            $ampm = $m[7] !== '' ? $m[7] : null;
            $rest = $m[8];
        } else {
            $rest = $m[6];
        }

        [$day, $month] = $this->dayFirst ? [$a, $b] : [$b, $a];

        if ($year < 100) {
            $year += 2000;
        }

        if ($ampm !== null) {
            if ($hour < 1 || $hour > 12) {
                return null;
            }
            $pm = strtolower($ampm) === 'p';
            $hour = $hour % 12 + ($pm ? 12 : 0);
        }

        if (! checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }

        $sender = null;
        $body = $rest;
        $separator = strpos($rest, ': ');

        if ($separator !== false && $separator > 0) {
            $sender = trim(substr($rest, 0, $separator));
            $body = substr($rest, $separator + 2);
        }

        // No iPhone, avisos e anexos aparecem com remetente e o texto começa com a marca U+200E.
        $marked = false;
        if ($sender !== null && ($pos = strpos($raw, ": \u{200E}")) !== false) {
            $marked = str_ends_with(TextNormalizer::clean(substr($raw, 0, $pos)), $sender);
        }

        return new RawMessage(
            line: $line,
            sentAt: sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second),
            sender: $sender === '' ? null : $sender,
            body: $body,
            marked: $marked,
        );
    }

    private function addUnrecognized(int $line, string $text): void
    {
        $this->unrecognizedCount++;

        if (count($this->unrecognized) < ImportFile::MAX_UNRECOGNIZED) {
            $this->unrecognized[] = ['line' => $line, 'text' => mb_substr(TextNormalizer::clean($text), 0, 500)];
        }
    }

    /**
     * Linhas do texto, numeradas a partir de 1, sem quebra de linha no fim.
     *
     * @param  (Closure(int $bytes): void)|null  $progress
     * @return Generator<int, string>
     */
    private function lines(?Closure $progress = null): Generator
    {
        $handle = ($this->open)();
        $n = 0;
        $bytes = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                $n++;
                $bytes += strlen($line);

                if ($n === 1 && str_starts_with($line, "\u{FEFF}")) {
                    $line = substr($line, 3);
                }

                if ($progress !== null && $n % 2000 === 0) {
                    $progress($bytes);
                }

                yield $n => rtrim($line, "\r\n");
            }
        } finally {
            fclose($handle);
        }

        if ($progress !== null) {
            $progress($bytes);
        }
    }
}
