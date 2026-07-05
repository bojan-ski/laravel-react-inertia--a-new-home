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
        $token = $this->faker->unique()->bothify('????????????????');

        return [
            'listing_id' => Listing::factory(),
            'disk' => 'public',
            'small_path' => "listings/small/{$token}.webp",
            'large_path' => "listings/large/{$token}.webp",
            'alt_text' => null,
            'sort_order' => 0,
        ];
    }
}
