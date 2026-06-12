<?php

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bookmark>
 */
class BookmarkFactory extends Factory
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
            'listing_id' => Listing::factory()->approved(),
        ];
    }
}
