<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::factory()->regularUser()->create([
            'name' => 'Sample Owner',
            'email' => 'owner@newbeginning.test',
        ]);

        Listing::factory()->count(20)->recycle($owner)->approved()->create();
        Listing::factory()->count(4)->recycle($owner)->pendingReview()->create();
        Listing::factory()->count(3)->recycle($owner)->rejected()->create();
    }
}
