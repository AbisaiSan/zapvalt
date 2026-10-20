<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'participant_id' => null,
            'sent_at' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d H:i:00'),
            'kind' => Message::TEXT,
            'body' => $this->faker->sentence(),
            'caption' => null,
            'system_icon' => null,
            'search_text' => fn (array $a) => Message::searchTextFor($a['kind'], $a['body'], $a['caption']),
            'dedupe_key' => fn () => Str::random(40).':0',
        ];
    }

    public function text(string $body): static
    {
        return $this->state(['kind' => Message::TEXT, 'body' => $body]);
    }

    public function ofKind(string $kind, ?string $body = null, ?string $caption = null): static
    {
        return $this->state(['kind' => $kind, 'body' => $body, 'caption' => $caption]);
    }

    public function system(string $body, ?string $icon = null): static
    {
        return $this->state(['kind' => Message::SYSTEM, 'body' => $body, 'system_icon' => $icon, 'participant_id' => null]);
    }
}
