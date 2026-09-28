<?php

namespace Database\Seeders;

use App\Enums\EuropeanCountry;
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
            ->whereNull('registration_number')
            ->each(function (Agency $agency): void {
                $country = fake()->randomElement([
                    EuropeanCountry::Germany,
                    EuropeanCountry::Austria,
                    EuropeanCountry::Croatia,
                    EuropeanCountry::Slovenia,
                    EuropeanCountry::Netherlands,
                ]);

                $agency->update([
                    'legal_name' => $agency->legal_name ?? $agency->name.' Ltd.',
                    'vat_id' => $country->value.fake()->numerify('#########'),
                    'registration_number' => fake()->numerify('REG-########'),
                    'registration_country' => $country,
                    'broker_register_number' => fake()->numerify('BR-#####'),
                ]);
            });
    }
}
