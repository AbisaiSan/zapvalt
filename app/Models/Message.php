<?php

namespace App\Models;

use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    use HasFactory;

    public const TEXT = 'text';

    public const IMAGE = 'image';

    public const VIDEO = 'video';

    public const AUDIO = 'audio';

    public const DOC = 'doc';

    public const STICKER = 'sticker';

    public const SYSTEM = 'system';

    public const CALL = 'call';

    public const DELETED = 'deleted';

    public const MISSING = 'missing';

    /** Tipos que têm arquivo; o nome do arquivo fica em body. */
    public const ATTACHMENT_KINDS = [self::IMAGE, self::VIDEO, self::AUDIO, self::DOC, self::STICKER];

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    /**
     * Texto pesquisável: corpo das mensagens de texto, legenda de fotos e vídeos e nome
     * de documentos. Avisos, apagadas, áudios, figurinhas e mídia faltante ficam de fora.
     */
    public static function searchTextFor(string $kind, ?string $body, ?string $caption): ?string
    {
        $source = match ($kind) {
            self::TEXT, self::DOC => $body,
            self::IMAGE, self::VIDEO => $caption,
            default => null,
        };

        return $source === null || $source === '' ? null : TextNormalizer::normalize($source);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function attachment(): HasOne
    {
        return $this->hasOne(Attachment::class);
    }
}
