<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $owner = User::factory()->regularUser()->create();

        return [
            'listing_id' => Listing::factory()->approved()->for($owner, 'owner'),
            'buyer_id' => User::factory()->regularUser(),
            'owner_id' => $owner->id,
        ];
    }
}
