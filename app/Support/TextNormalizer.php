<?php

namespace App\Support;

use Normalizer;

/**
 * Normalização para busca: sem acentos, minúsculas e sem caracteres invisíveis.
 * Mesma regra da função norm do protótipo (resources/js/lib/search.ts no frontend).
 */
class TextNormalizer
{
    /** Marcas de direção e BOM que o WhatsApp insere nos exports. */
    public const INVISIBLE = '/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{FEFF}]/u';

    /** Espaços especiais trocados por espaço comum. */
    public const SPECIAL_SPACES = ["\u{202F}", "\u{00A0}"];

    /** Usada quando a extensão intl não está disponível. */
    private const FALLBACK = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a', 'Å' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u',
        'ç' => 'c', 'Ç' => 'c', 'ñ' => 'n', 'Ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y', 'Ý' => 'y',
    ];

    /** Remove caracteres invisíveis e troca espaços especiais, sem mudar o resto. */
    public static function clean(string $text): string
    {
        $text = preg_replace(self::INVISIBLE, '', $text) ?? $text;

        return str_replace(self::SPECIAL_SPACES, ' ', $text);
    }

    public static function normalize(string $text): string
    {
        $text = self::clean($text);

        if (class_exists(Normalizer::class)) {
            $decomposed = Normalizer::normalize($text, Normalizer::FORM_D);
            $text = preg_replace('/[\x{0300}-\x{036F}]/u', '', $decomposed === false ? $text : $decomposed) ?? $text;
        } else {
            $text = strtr($text, self::FALLBACK);
        }

        return mb_strtolower($text, 'UTF-8');
    }

    /** Termo de busca já normalizado como frase FTS5 (entre aspas, com aspas internas dobradas). */
    public static function ftsPhrase(string $normalizedTerm): string
    {
        return '"'.str_replace('"', '""', $normalizedTerm).'"';
    }

    /** Escapa % e _ para uso em LIKE com ESCAPE '\'. */
    public static function likeEscape(string $normalizedTerm): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $normalizedTerm);
    }
}
