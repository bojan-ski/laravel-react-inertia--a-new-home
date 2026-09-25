<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'handled_at' => null,
            'handled_by_user_id' => null,
        ];
    }

    public function handled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'handled_at' => now(),
            'handled_by_user_id' => User::factory()->admin(),
        ]);
    }
}
