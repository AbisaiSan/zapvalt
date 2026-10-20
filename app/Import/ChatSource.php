<?php

namespace App\Import;

use App\Import\Media\MediaSource;
use Closure;

/**
 * Uma conversa encontrada em um arquivo importado (um .txt, ou um texto dentro de um .zip).
 */
class ChatSource
{
    /**
     * @param  Closure(): resource  $open
     */
    public function __construct(
        /** Nome mostrado no cartão de importação. */
        public string $label,
        /** Nome sugerido para a conversa, tirado do nome do arquivo. */
        public string $conversationName,
        public Closure $open,
        public int $size,
        public MediaSource $media,
    ) {}
}
