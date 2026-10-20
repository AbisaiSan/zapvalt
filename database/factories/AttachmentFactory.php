<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        $sha = hash('sha256', $this->faker->uuid());

        return [
            'message_id' => Message::factory()->ofKind(Message::IMAGE, 'IMG-20261019-WA0007.jpg'),
            'original_name' => 'IMG-20261019-WA0007.jpg',
            'ext' => 'jpg',
            'mime' => 'image/jpeg',
            'size_bytes' => $this->faker->numberBetween(10_000, 500_000),
            'sha256' => $sha,
            'storage_path' => 'media/'.substr($sha, 0, 2).'/'.substr($sha, 2, 2)."/{$sha}.jpg",
            'width' => 1600,
            'height' => 1200,
        ];
    }
}
