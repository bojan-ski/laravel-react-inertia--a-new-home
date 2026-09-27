<?php

namespace Database\Seeders;

use App\Enums\UsState;
use App\Models\Agency;
use Illuminate\Database\Seeder;

class AgencyBusinessDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Agency::query()
            ->whereNull('legal_name')
            ->each(fn (Agency $agency) => $agency->update([
                'legal_name' => $agency->name.' LLC',
                'ein' => fake()->numerify('##-#######'),
                'license_number' => fake()->bothify('#######'),
                'license_state' => fake()->randomElement(UsState::cases()),
            ]));
    }
}
