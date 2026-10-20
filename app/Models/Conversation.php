<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    public const DRAFT = 'draft';

    public const ACTIVE = 'active';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
            'first_message_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    /** Rascunhos nunca aparecem em listas nem na busca. */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::ACTIVE);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }
}
