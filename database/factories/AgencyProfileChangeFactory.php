<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyProfileChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgencyProfileChange>
 */
class AgencyProfileChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory()->approved(),
            'proposed_values' => ['description' => fake()->paragraph()],
        ];
    }
}
