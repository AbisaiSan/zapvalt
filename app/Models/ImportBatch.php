<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const OPEN = 'open';

    public const COMMITTED = 'committed';

    public const CANCELLED = 'cancelled';

    protected $fillable = ['status', 'committed_at'];

    protected function casts(): array
    {
        return ['committed_at' => 'datetime'];
    }

    public function files(): HasMany
    {
        return $this->hasMany(ImportFile::class, 'batch_id');
    }
}
