<?php

namespace Tests\Feature;

use App\Enums\UsState;
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
            'name' => 'Golden Gate Realty',
            'legal_name' => 'Golden Gate Realty Group LLC',
            'ein' => '94-1234567',
            'license_number' => '01987654',
            'license_state' => 'CA',
            'email' => 'hello@goldengate.test',
        ], $overrides);
    }

    public function test_setup_stores_business_details(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)->post('/agency/setup', $this->payload())->assertRedirect('/agency');

        $agency = Agency::sole();
        $this->assertSame('Golden Gate Realty Group LLC', $agency->legal_name);
        $this->assertSame('94-1234567', $agency->ein);
        $this->assertSame('01987654', $agency->license_number);
        $this->assertSame(UsState::California, $agency->license_state);
    }

    public function test_ein_is_encrypted_at_rest(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)->post('/agency/setup', $this->payload());

        $raw = DB::table('agencies')->value('ein');
        $this->assertNotSame('94-1234567', $raw);
        $this->assertStringNotContainsString('1234567', $raw);
    }

    public function test_business_details_are_required(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->post('/agency/setup', $this->payload([
                'legal_name' => '',
                'ein' => '',
                'license_number' => '',
                'license_state' => '',
            ]))
            ->assertSessionHasErrors(['legal_name', 'ein', 'license_number', 'license_state']);

        $this->assertDatabaseCount('agencies', 0);
    }

    public function test_ein_format_is_validated(): void
    {
        $user = User::factory()->agency()->create();

        foreach (['941234567', '94-12345', 'AB-1234567'] as $ein) {
            $this->actingAs($user)
                ->post('/agency/setup', $this->payload(['ein' => $ein]))
                ->assertSessionHasErrors(['ein' => __('app.agency_business.validation.ein')]);
        }
    }

    public function test_license_state_must_be_a_us_state(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->post('/agency/setup', $this->payload(['license_state' => 'ZZ']))
            ->assertSessionHasErrors('license_state');
    }

    public function test_license_number_characters_are_validated(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->post('/agency/setup', $this->payload(['license_number' => '12 34/56']))
            ->assertSessionHasErrors(['license_number' => __('app.agency_business.validation.license_number')]);
    }

    public function test_setup_and_edit_pages_receive_state_options(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->get('/agency/setup')
            ->assertInertia(fn (Assert $page) => $page
                ->where('usStates.CA', 'California')
                ->has('usStates', count(UsState::cases())));

        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user->refresh())
            ->get('/agency/edit')
            ->assertInertia(fn (Assert $page) => $page
                ->has('usStates', count(UsState::cases()))
                ->has('agency.legalName')
                ->has('agency.ein'));
    }

    public function test_every_enum_state_has_a_display_name(): void
    {
        foreach (UsState::cases() as $state) {
            $this->assertArrayHasKey($state->value, __('app.us_states'));
        }
    }

    public function test_public_pages_show_the_license_but_never_the_ein_or_legal_name(): void
    {
        $agency = Agency::factory()->approved()->create([
            'legal_name' => 'Secret Holdings LLC',
            'ein' => '11-2223333',
            'license_number' => '01555444',
            'license_state' => 'TX',
        ]);

        $this->get("/agencies/{$agency->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('agency.licenseNumber', '01555444')
                ->where('agency.licenseState', 'TX')
                ->missing('agency.ein')
                ->missing('agency.legalName'))
            ->assertDontSee('11-2223333')
            ->assertDontSee('Secret Holdings LLC');

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agencies.data.0.licenseNumber', '01555444')
                ->missing('agencies.data.0.ein'))
            ->assertDontSee('11-2223333');
    }

    public function test_admin_moderation_queue_shows_all_business_details(): void
    {
        Agency::factory()->pendingVerification()->create([
            'legal_name' => 'Pending Holdings LLC',
            'ein' => '22-3334444',
            'license_number' => '7788',
            'license_state' => 'FL',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agencies.data.0.legalName', 'Pending Holdings LLC')
                ->where('agencies.data.0.ein', '22-3334444')
                ->where('agencies.data.0.licenseNumber', '7788')
                ->where('agencies.data.0.licenseState', 'FL'));
    }

    public function test_owner_dashboard_shows_business_details(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id, 'ein' => '33-4445555']);

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page->where('agency.ein', '33-4445555'));
    }

    public function test_backfill_seeder_fills_only_agencies_missing_details(): void
    {
        $missing = Agency::factory()->approved()->create([
            'legal_name' => null,
            'ein' => null,
            'license_number' => null,
            'license_state' => null,
        ]);
        $complete = Agency::factory()->approved()->create(['legal_name' => 'Keep Me LLC', 'ein' => '44-5556666']);

        $this->seed(AgencyBusinessDetailsSeeder::class);

        $missing->refresh();
        $this->assertSame($missing->name.' LLC', $missing->legal_name);
        $this->assertMatchesRegularExpression('/^\d{2}-\d{7}$/', $missing->ein);
        $this->assertNotNull($missing->license_number);
        $this->assertInstanceOf(UsState::class, $missing->license_state);

        $complete->refresh();
        $this->assertSame('Keep Me LLC', $complete->legal_name);
        $this->assertSame('44-5556666', $complete->ein);
    }
}
