<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Participant;
use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'name' => $this->faker->unique()->name(),
            'name_normalized' => fn (array $attributes) => TextNormalizer::normalize($attributes['name']),
            'is_me' => false,
            'message_count' => 0,
        ];
    }

    public function me(): static
    {
        return $this->state(['is_me' => true]);
    }
}
