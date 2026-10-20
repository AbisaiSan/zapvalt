<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\ImportFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportFile>
 */
class ImportFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'batch_id' => ImportBatch::factory(),
            'original_name' => 'Conversa do WhatsApp com '.$this->faker->name().'.txt',
            'ext' => 'txt',
            'size_bytes' => $this->faker->numberBetween(1_000, 100_000),
            'status' => ImportFile::WAITING,
        ];
    }
}
