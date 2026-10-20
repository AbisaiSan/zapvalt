<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->name();

        return [
            'name' => $name,
            'name_normalized' => fn (array $attributes) => TextNormalizer::normalize($attributes['name']),
            'is_group' => false,
            'avatar_index' => $this->faker->numberBetween(0, 7),
            'status' => Conversation::ACTIVE,
            'source_file_name' => "Conversa do WhatsApp com {$name}.txt",
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => Conversation::DRAFT]);
    }

    public function group(): static
    {
        return $this->state(fn () => [
            'name' => 'Grupo '.$this->faker->words(2, true),
            'is_group' => true,
        ]);
    }
}
