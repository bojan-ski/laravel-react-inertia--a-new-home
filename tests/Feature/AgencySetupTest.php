<?php

namespace Tests\Feature;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\User;
use App\Services\AgencyService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencySetupTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Premier Properties',
            'email' => 'contact@premier.test',
            'phone' => '+1 555 0100',
            'website' => 'https://premier.test',
            'address' => '12 Market Street, Belgrade',
            'description' => 'A boutique agency specialising in city apartments.',
        ], $overrides);
    }

    public function test_agency_user_without_record_can_view_setup(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->get('/agency/setup')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Agency/Setup'));
    }

    public function test_agency_user_with_record_is_redirected_from_setup(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/agency/setup')->assertRedirect('/agency');
    }

    public function test_regular_user_gets_403(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->get('/agency/setup')->assertForbidden();
        $this->actingAs($user)->post('/agency/setup', $this->validPayload())->assertForbidden();
    }

    public function test_admin_gets_403(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/agency/setup')->assertForbidden();
        $this->actingAs($admin)->post('/agency/setup', $this->validPayload())->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/agency/setup')->assertRedirect('/login');
        $this->post('/agency/setup', $this->validPayload())->assertRedirect('/login');
    }

    public function test_agency_user_can_submit_and_create_the_record(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->post('/agency/setup', $this->validPayload())
            ->assertRedirect('/agency')
            ->assertSessionHas('success', 'Agency profile submitted for verification.');

        $this->assertDatabaseHas('agencies', [
            'user_id' => $user->id,
            'name' => 'Premier Properties',
            'email' => 'contact@premier.test',
        ]);
    }

    public function test_created_record_has_expected_defaults(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)->post('/agency/setup', $this->validPayload());

        $agency = Agency::firstOrFail();

        $this->assertSame($user->id, $agency->user_id);
        $this->assertSame('premier-properties', $agency->slug);
        $this->assertSame(AgencyStatus::PendingVerification, $agency->status);
        $this->assertNull($agency->approved_at);
        $this->assertNull($agency->rejection_reason);
        $this->assertNull($agency->logo_path);
    }

    public function test_slug_collisions_append_suffix(): void
    {
        $first = User::factory()->agency()->create();
        $second = User::factory()->agency()->create();

        $this->actingAs($first)->post('/agency/setup', $this->validPayload(['name' => 'Crown Estates']));
        $this->actingAs($second)->post('/agency/setup', $this->validPayload(['name' => 'Crown Estates']));

        $slugs = Agency::orderBy('id')->pluck('slug')->all();

        $this->assertSame(['crown-estates', 'crown-estates-2'], $slugs);
    }

    public function test_submitting_after_creation_redirects_without_duplicating(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post('/agency/setup', $this->validPayload())
            ->assertRedirect('/agency');

        $this->assertSame(1, Agency::where('user_id', $user->id)->count());
    }

    public function test_service_throws_when_user_already_has_an_agency(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->create(['user_id' => $user->id]);

        $this->expectException(\RuntimeException::class);

        app(AgencyService::class)->createForUser($user->fresh(), $this->validPayload());
    }

    public function test_service_forces_moderation_defaults_regardless_of_data(): void
    {
        $user = User::factory()->agency()->create();

        $agency = app(AgencyService::class)->createForUser($user, $this->validPayload([
            'status' => AgencyStatus::Approved->value,
            'approved_at' => now()->toDateTimeString(),
            'rejection_reason' => 'should be ignored',
            'logo_path' => 'logos/hacked.png',
            'slug' => 'hacked-slug',
            'user_id' => 99999,
        ]));

        $this->assertSame($user->id, $agency->user_id);
        $this->assertSame('premier-properties', $agency->slug);
        $this->assertSame(AgencyStatus::PendingVerification, $agency->status);
        $this->assertNull($agency->approved_at);
        $this->assertNull($agency->rejection_reason);
        $this->assertNull($agency->logo_path);
    }

    public function test_new_agency_user_registration_redirects_to_setup(): void
    {
        $this->post('/register', [
            'name' => 'New Agency',
            'email' => 'new@agency.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'agency',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ])->assertRedirect('/agency/setup');
    }

    public function test_visiting_agency_without_a_record_redirects_to_setup(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->get('/agency')
            ->assertRedirect('/agency/setup');
    }

    public function test_visiting_agency_edit_without_a_record_redirects_to_setup(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->get('/agency/edit')
            ->assertRedirect('/agency/setup');
    }

    public function test_optional_fields_can_be_omitted(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->post('/agency/setup', [
                'name' => 'Minimal Agency',
                'email' => 'minimal@agency.test',
            ])
            ->assertRedirect('/agency');

        $agency = Agency::firstOrFail();

        $this->assertNull($agency->phone);
        $this->assertNull($agency->website);
        $this->assertNull($agency->address);
        $this->assertNull($agency->description);
    }

    public function test_agency_user_with_record_can_view_the_dashboard(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Agency/Dashboard'));
    }

    public function test_validation_fails_for_missing_required_fields(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->post('/agency/setup', [])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertDatabaseCount('agencies', 0);
    }

    public function test_validation_rejects_invalid_email(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->post('/agency/setup', $this->validPayload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');
    }

    public function test_validation_rejects_invalid_website_url(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->post('/agency/setup', $this->validPayload(['website' => 'not a url']))
            ->assertSessionHasErrors('website');
    }

    public function test_server_controlled_fields_are_stripped_by_the_request(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)->post('/agency/setup', $this->validPayload([
            'status' => AgencyStatus::Approved->value,
            'approved_at' => now()->toDateTimeString(),
            'user_id' => 99999,
            'slug' => 'hacked-slug',
        ]));

        $agency = Agency::firstOrFail();

        $this->assertSame($user->id, $agency->user_id);
        $this->assertSame('premier-properties', $agency->slug);
        $this->assertSame(AgencyStatus::PendingVerification, $agency->status);
        $this->assertNull($agency->approved_at);
    }
}
