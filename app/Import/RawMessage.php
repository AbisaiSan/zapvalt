<?php

namespace App\Import;

/**
 * Mensagem como saiu do texto exportado, antes da classificação.
 */
class RawMessage
{
    public function __construct(
        public int $line,
        public string $sentAt,
        public ?string $sender,
        public string $body,
        /** Corpo marcado com U+200E logo após o remetente (avisos e anexos no iPhone). */
        public bool $marked = false,
    ) {}
}
