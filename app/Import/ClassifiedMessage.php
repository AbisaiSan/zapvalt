<?php

namespace App\Import;

/**
 * Mensagem já classificada, pronta para gravar.
 */
class ClassifiedMessage
{
    public function __construct(
        public int $line,
        public string $sentAt,
        public string $kind,
        /** Remetente; nulo em avisos e chamadas. */
        public ?string $sender,
        public ?string $body,
        public ?string $caption = null,
        public ?string $systemIcon = null,
        /** Nome do arquivo citado no texto, quando houver. */
        public ?string $fileName = null,
        /** Tipo usado na chave de duplicidade (o tipo do arquivo, mesmo se ele faltar). */
        public ?string $dedupeKind = null,
        /** Remetente original, mesmo em avisos do iPhone (para a detecção de grupo). */
        public ?string $rawSender = null,
    ) {
        $this->dedupeKind ??= $kind;
    }
}
