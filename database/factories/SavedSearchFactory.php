<?php

namespace Database\Factories;

use App\Http\Requests\ListingIndexRequest;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<SavedSearch>
 */
class SavedSearchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->regularUser(),
            'name' => fake()->words(3, true),
            'filters' => $this->filterSet(['listingType' => fake()->randomElement(['sale', 'rent'])]),
            'alerts_enabled' => true,
            'last_alerted_at' => now(),
        ];
    }

    /**
     * @param  array<string, string|list<string>>  $filters
     */
    public function withFilters(array $filters): static
    {
        return $this->state(fn (array $attributes): array => [
            'filters' => $this->filterSet($filters),
        ]);
    }

    public function alertsDisabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'alerts_enabled' => false,
        ]);
    }

    /**
     * @param  array<string, string|list<string>>  $filters
     * @return array<string, string|list<string>>
     */
    private function filterSet(array $filters): array
    {
        return Arr::except(ListingIndexRequest::normalizeFilters($filters), 'sort');
    }
}
