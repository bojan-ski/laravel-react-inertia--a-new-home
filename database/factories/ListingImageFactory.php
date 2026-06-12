<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingImage>
 */
class ListingImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->slug(2);

        return [
            'listing_id' => Listing::factory(),
            'original_path' => "listings/{$name}.jpg",
            'small_webp_path' => "listings/{$name}-small.webp",
            'large_webp_path' => "listings/{$name}-large.webp",
            'sort_order' => 0,
        ];
    }
}
