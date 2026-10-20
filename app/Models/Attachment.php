<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
