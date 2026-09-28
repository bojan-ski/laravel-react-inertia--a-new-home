<?php

namespace Database\Factories;

use App\Enums\AgencyStatus;
use App\Enums\EuropeanCountry;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Agency>
 */
class AgencyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->generateName();
        $country = fake()->randomElement([
            EuropeanCountry::Germany,
            EuropeanCountry::Austria,
            EuropeanCountry::Croatia,
            EuropeanCountry::Slovenia,
            EuropeanCountry::Netherlands,
        ]);

        return [
            'user_id' => User::factory()->agency(),
            'name' => $name,
            'legal_name' => $name.' Ltd.',
            'vat_id' => $country->value.fake()->numerify('#########'),
            'registration_number' => fake()->numerify('REG-########'),
            'registration_country' => $country,
            'broker_register_number' => fake()->numerify('BR-#####'),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('######'),
            'description' => fake()->paragraphs(2, true),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'website' => 'https://'.fake()->domainName(),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'logo_path' => null,
            'status' => AgencyStatus::PendingVerification,
            'rejection_reason' => null,
            'approved_at' => null,
        ];
    }

    private function generateName(): string
    {
        $prefix = fake()->randomElement([
            'Premier', 'Elite', 'Crown', 'Horizon', 'Summit',
            'Heritage', 'Vista', 'Prime', 'Coastal', 'Capital',
        ]);

        $suffix = fake()->randomElement([
            'Properties', 'Real Estate', 'Homes', 'Realty', 'Estates',
        ]);

        return "{$prefix} {$suffix}";
    }

    public function pendingVerification(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AgencyStatus::PendingVerification,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AgencyStatus::Approved,
            'approved_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
            'rejection_reason' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AgencyStatus::Rejected,
            'approved_at' => null,
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AgencyStatus::Hidden,
            'approved_at' => null,
        ]);
    }
}
