<?php

namespace Tests\Feature;

use App\Enums\EuropeanCountry;
use App\Models\Agency;
use App\Models\User;
use Database\Seeders\AgencyBusinessDetailsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencyBusinessDetailsTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Donau Realty',
            'legal_name' => 'Donau Realty GmbH',
            'vat_id' => 'ATU12345678',
            'registration_number' => 'FN 123456a',
            'registration_country' => 'AT',
            'broker_register_number' => 'IMM-2024-017',
            'email' => 'hello@donau.test',
        ], $overrides);
    }

    public function test_setup_stores_business_details(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)->post('/agency/setup', $this->payload())->assertRedirect('/agency');

        $agency = Agency::sole();
        $this->assertSame('Donau Realty GmbH', $agency->legal_name);
        $this->assertSame('ATU12345678', $agency->vat_id);
        $this->assertSame('FN 123456a', $agency->registration_number);
        $this->assertSame(EuropeanCountry::Austria, $agency->registration_country);
        $this->assertSame('IMM-2024-017', $agency->broker_register_number);
    }

    public function test_vat_id_is_uppercased_and_stripped_of_spaces_before_storage(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->post('/agency/setup', $this->payload(['vat_id' => ' de 123 456 789 ']))
            ->assertSessionHasNoErrors();

        $this->assertSame('DE123456789', Agency::sole()->vat_id);
    }

    public function test_vat_id_is_stored_as_plain_text(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)->post('/agency/setup', $this->payload());

        $this->assertSame('ATU12345678', DB::table('agencies')->value('vat_id'));
    }

    public function test_legal_name_registration_number_and_country_are_required(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->post('/agency/setup', $this->payload([
                'legal_name' => '',
                'registration_number' => '',
                'registration_country' => '',
            ]))
            ->assertSessionHasErrors(['legal_name', 'registration_number', 'registration_country']);

        $this->assertDatabaseCount('agencies', 0);
    }

    public function test_vat_id_and_broker_register_number_are_optional(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->post('/agency/setup', $this->payload(['vat_id' => '', 'broker_register_number' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/agency');

        $agency = Agency::sole();
        $this->assertNull($agency->vat_id);
        $this->assertNull($agency->broker_register_number);
    }

    public function test_vat_id_format_is_validated(): void
    {
        $user = User::factory()->agency()->create();

        foreach (['123456789', 'D1234567', 'DE1', 'DE12345678901234', 'DE-123456789'] as $vatId) {
            $this->actingAs($user)
                ->post('/agency/setup', $this->payload(['vat_id' => $vatId]))
                ->assertSessionHasErrors(['vat_id' => __('app.agency_business.validation.vat_id')]);
        }

        $this->assertDatabaseCount('agencies', 0);
    }

    public function test_registration_country_must_be_a_european_country(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->post('/agency/setup', $this->payload(['registration_country' => 'US']))
            ->assertSessionHasErrors('registration_country');
    }

    public function test_registration_number_characters_are_validated(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->post('/agency/setup', $this->payload(['registration_number' => 'HRB#12345']))
            ->assertSessionHasErrors(['registration_number' => __('app.agency_business.validation.registration_number')]);
    }

    public function test_registration_number_accepts_register_style_punctuation(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->post('/agency/setup', $this->payload(['registration_number' => 'MBS 080/123.456-7']))
            ->assertSessionHasNoErrors();

        $this->assertSame('MBS 080/123.456-7', Agency::sole()->registration_number);
    }

    public function test_setup_and_edit_pages_receive_country_options(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->get('/agency/setup')
            ->assertInertia(fn (Assert $page) => $page
                ->where('countries.DE', 'Germany')
                ->has('countries', count(EuropeanCountry::cases()))
                ->missing('usStates'));

        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user->refresh())
            ->get('/agency/edit')
            ->assertInertia(fn (Assert $page) => $page
                ->has('countries', count(EuropeanCountry::cases()))
                ->has('agency.legalName')
                ->has('agency.vatId')
                ->has('agency.registrationNumber')
                ->has('agency.registrationCountry')
                ->has('agency.brokerRegisterNumber'));
    }

    public function test_every_enum_country_has_a_display_name(): void
    {
        $this->assertCount(count(EuropeanCountry::cases()), __('app.european_countries'));

        foreach (EuropeanCountry::cases() as $country) {
            $this->assertArrayHasKey($country->value, __('app.european_countries'));
        }
    }

    public function test_public_pages_show_registration_details_but_never_the_legal_name(): void
    {
        $agency = Agency::factory()->approved()->create([
            'legal_name' => 'Secret Holdings d.o.o.',
            'vat_id' => 'HR12345678901',
            'registration_number' => '080123456',
            'registration_country' => 'HR',
            'broker_register_number' => 'BR-77001',
        ]);

        $this->get("/agencies/{$agency->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('agency.registrationNumber', '080123456')
                ->where('agency.registrationCountry', 'HR')
                ->where('agency.brokerRegisterNumber', 'BR-77001')
                ->where('agency.vatId', 'HR12345678901')
                ->where('translations.registration', __('app.agencies.registration'))
                ->missing('agency.legalName'))
            ->assertDontSee('Secret Holdings d.o.o.');

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agencies.data.0.registrationNumber', '080123456')
                ->where('agencies.data.0.vatId', 'HR12345678901')
                ->missing('agencies.data.0.legalName'))
            ->assertDontSee('Secret Holdings d.o.o.');
    }

    public function test_admin_moderation_queue_shows_all_business_details(): void
    {
        Agency::factory()->pendingVerification()->create([
            'legal_name' => 'Pending Holdings B.V.',
            'vat_id' => 'NL123456789B01',
            'registration_number' => 'KVK 12345678',
            'registration_country' => 'NL',
            'broker_register_number' => null,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agencies.data.0.legalName', 'Pending Holdings B.V.')
                ->where('agencies.data.0.vatId', 'NL123456789B01')
                ->where('agencies.data.0.registrationNumber', 'KVK 12345678')
                ->where('agencies.data.0.registrationCountry', 'NL')
                ->where('agencies.data.0.brokerRegisterNumber', null));
    }

    public function test_owner_dashboard_shows_business_details(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create([
            'user_id' => $user->id,
            'vat_id' => 'SI12345678',
            'registration_country' => 'SI',
        ]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agency.vatId', 'SI12345678')
                ->where('agency.registrationCountry', 'SI'));
    }

    public function test_backfill_seeder_fills_only_agencies_missing_details(): void
    {
        $missing = Agency::factory()->approved()->create([
            'legal_name' => null,
            'vat_id' => null,
            'registration_number' => null,
            'registration_country' => null,
            'broker_register_number' => null,
        ]);
        $complete = Agency::factory()->approved()->create([
            'legal_name' => 'Keep Me GmbH',
            'vat_id' => null,
            'registration_number' => 'HRB 99999',
        ]);

        $this->seed(AgencyBusinessDetailsSeeder::class);

        $missing->refresh();
        $this->assertSame($missing->name.' Ltd.', $missing->legal_name);
        $this->assertMatchesRegularExpression('/^[A-Z]{2}[A-Z0-9]{2,13}$/', $missing->vat_id);
        $this->assertStringStartsWith($missing->registration_country->value, $missing->vat_id);
        $this->assertNotNull($missing->registration_number);
        $this->assertInstanceOf(EuropeanCountry::class, $missing->registration_country);

        $complete->refresh();
        $this->assertSame('Keep Me GmbH', $complete->legal_name);
        $this->assertSame('HRB 99999', $complete->registration_number);
        $this->assertNull($complete->vat_id);

        $vatAfterFirstRun = $missing->vat_id;
        $this->seed(AgencyBusinessDetailsSeeder::class);
        $this->assertSame($vatAfterFirstRun, $missing->refresh()->vat_id);
    }

    public function test_factory_produces_valid_eu_business_details(): void
    {
        $agency = Agency::factory()->create();

        $this->assertMatchesRegularExpression('/^[A-Z]{2}[A-Z0-9]{2,13}$/', $agency->vat_id);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\/.\- ]+$/', $agency->registration_number);
        $this->assertInstanceOf(EuropeanCountry::class, $agency->registration_country);
        $this->assertNotNull($agency->broker_register_number);
    }
}
