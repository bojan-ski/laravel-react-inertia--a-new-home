<?php

namespace Database\Factories;

use App\Enums\ListingReportReason;
use App\Enums\ListingReportResolution;
use App\Models\Listing;
use App\Models\ListingReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingReport>
 */
class ListingReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory()->approved(),
            'reported_by_user_id' => User::factory()->regularUser(),
            'reason' => fake()->randomElement(ListingReportReason::cases()),
            'details' => fake()->optional()->sentence(),
            'resolved_at' => null,
            'resolved_by_user_id' => null,
            'resolution' => null,
        ];
    }

    public function resolved(ListingReportResolution $resolution = ListingReportResolution::Dismissed): static
    {
        return $this->state(fn (array $attributes): array => [
            'resolved_at' => now(),
            'resolved_by_user_id' => User::factory()->admin(),
            'resolution' => $resolution,
        ]);
    }
}
