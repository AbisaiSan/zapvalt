<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportFile extends Model
{
    use HasFactory;

    public const UPLOADING = 'uploading';

    public const WAITING = 'waiting';

    public const PROCESSING = 'processing';

    public const DONE = 'done';

    public const PARTIAL = 'partial';

    public const FAILED = 'failed';

    public const MERGE = 'merge';

    public const SEPARATE = 'separate';

    /** Máximo de linhas não reconhecidas guardadas por arquivo. */
    public const MAX_UNRECOGNIZED = 200;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
            'unrecognized_lines' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function existingConversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'existing_conversation_id');
    }
}
