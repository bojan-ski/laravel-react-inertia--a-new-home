<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->generateTitle();

        return [
            'user_id' => User::factory()->regularUser(),
            'agency_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('######'),
            'listing_type' => fake()->randomElement(['sale', 'rent']),
            'property_type' => fake()->randomElement([
                'house', 'flat', 'apartment', 'cottage', 'villa',
                'condo', 'land', 'commercial', 'other',
            ]),
            'status' => ListingStatus::Approved,
            'price' => fake()->numberBetween(50_000, 950_000),
            'currency' => Listing::CURRENCY,
            'description' => fake()->paragraphs(3, true),
            'country' => fake()->randomElement(['Serbia', 'Croatia', 'Slovenia', 'Austria', 'Germany']),
            'city' => fake()->city(),
            'address_line' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'bedrooms' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->numberBetween(1, 3),
            'area_value' => fake()->numberBetween(45, 320),
            'area_unit' => Listing::AREA_UNIT,
            'year_built' => fake()->numberBetween(1960, 2024),
            'contact_name' => null,
            'contact_email' => null,
            'contact_phone' => null,
            'approved_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
            'expires_at' => now()->addDays(fake()->numberBetween(30, Listing::LIFETIME_DAYS)),
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }

    private function generateTitle(): string
    {
        $adjective = fake()->randomElement([
            'Modern', 'Spacious', 'Cozy', 'Elegant', 'Charming',
            'Luxurious', 'Bright', 'Renovated', 'Stylish', 'Sunny',
        ]);

        $type = fake()->randomElement([
            'City Apartment', 'Family House', 'Studio Flat', 'Country Cottage',
            'Seaside Villa', 'Downtown Condo', 'Garden Townhouse', 'Penthouse',
        ]);

        return "{$adjective} {$type}";
    }

    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::PendingReview,
            'approved_at' => null,
            'expires_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Approved,
            'approved_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
            'expires_at' => now()->addDays(fake()->numberBetween(30, Listing::LIFETIME_DAYS)),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->approved()->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Rejected,
            'approved_at' => null,
            'expires_at' => null,
            'rejected_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Hidden,
            'approved_at' => null,
            'expires_at' => null,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_type' => 'sale',
            'status' => ListingStatus::Sold,
            'approved_at' => fake()->dateTimeBetween('-6 months', '-1 week'),
            'closed_at' => now()->subDay(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function rented(): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_type' => 'rent',
            'status' => ListingStatus::Rented,
            'approved_at' => fake()->dateTimeBetween('-6 months', '-1 week'),
            'closed_at' => now()->subDay(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }
}
