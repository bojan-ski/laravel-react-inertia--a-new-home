<?php

namespace Database\Seeders;

use App\Models\Agency;
use Illuminate\Database\Seeder;

class AgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Agency::factory()->count(4)->pendingVerification()->create();
        Agency::factory()->count(5)->approved()->create();
        Agency::factory()->count(2)->rejected()->create();
    }
}
