<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\ReportedChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportedChatMessage>
 */
class ReportedChatMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_message_id' => ChatMessage::factory(),
            'reported_by_user_id' => User::factory()->regularUser(),
            'offending_user_id' => User::factory()->regularUser(),
            'reason' => fake()->sentence(),
            'resolved_at' => null,
            'resolved_by_user_id' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'resolved_at' => now(),
            'resolved_by_user_id' => User::factory()->admin(),
        ]);
    }
}
