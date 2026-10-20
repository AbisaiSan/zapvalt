<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    public const WAITING = 'waiting';

    public const RUNNING = 'running';

    public const DONE = 'done';

    public const FAILED = 'failed';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'percent' => (int) $this->percent,
            'file_name' => $this->file_path === null ? null : basename($this->file_path),
            'size_bytes' => $this->size_bytes === null ? null : (int) $this->size_bytes,
            'error' => $this->error,
        ];
    }
}
