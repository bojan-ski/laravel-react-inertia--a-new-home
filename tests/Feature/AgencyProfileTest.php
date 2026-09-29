<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\User;
use App\Policies\AgencyPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencyProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Updated Agency Name',
            'legal_name' => 'Updated Agency LLC',
            'vat_id' => 'DE123456789',
            'registration_number' => '01234567',
            'registration_country' => 'DE',
            'email' => 'updated@agency.test',
            'phone' => '+1 555 9999',
            'website' => 'https://updated.test',
            'address' => '99 New Street, Belgrade',
            'description' => 'Updated description of the agency.',
        ], $overrides);
    }

    private function agencyListing(Agency $agency, string $state = 'approved', array $overrides = []): Listing
    {
        return Listing::factory()->{$state}()->create(array_merge([
            'user_id' => null,
            'agency_id' => $agency->id,
        ], $overrides));
    }

    public function test_agency_user_can_view_own_dashboard(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agency/Show')
                ->has('agency')
                ->has('listings'));
    }

    public function test_dashboard_receives_navbar_translations(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('nav', __('app.nav'))
                ->missing('listingsNav'));
    }

    public function test_dashboard_lists_agency_listings_newest_first(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        $older = $this->agencyListing($agency, 'approved', ['created_at' => now()->subDays(3)]);
        $newer = $this->agencyListing($agency, 'approved', ['created_at' => now()->subDay()]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 2)
                ->where('listings.0.id', $newer->id)
                ->where('listings.1.id', $older->id));
    }

    public function test_dashboard_excludes_soft_deleted_listings(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->agencyListing($agency);
        $this->agencyListing($agency)->delete();

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page->has('listings', 1));
    }

    public function test_dashboard_shows_all_listing_statuses(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->agencyListing($agency, 'pendingReview');
        $this->agencyListing($agency, 'approved');
        $this->agencyListing($agency, 'rejected');
        $this->agencyListing($agency, 'hidden');

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page->has('listings', 4));
    }

    public function test_rejected_listing_shows_rejection_reason(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->agencyListing($agency, 'rejected', ['rejection_reason' => 'Incomplete details.']);

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.0.rejectionReason', 'Incomplete details.'));
    }

    public function test_approved_status_renders_with_approval_timestamp(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agency.status', 'approved')
                ->whereNot('agency.approvedAt', null));
    }

    public function test_pending_status_renders(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->pendingVerification()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agency.status', 'pending_verification')
                ->where('agency.approvedAt', null));
    }

    public function test_rejected_status_shows_rejection_reason(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->rejected()->create([
            'user_id' => $user->id,
            'rejection_reason' => 'Did not meet requirements.',
        ]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agency.status', 'rejected')
                ->where('agency.rejectionReason', 'Did not meet requirements.'));
    }

    public function test_agency_user_can_view_edit_form_prefilled(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create([
            'user_id' => $user->id,
            'name' => 'Prefilled Agency',
            'email' => 'prefilled@agency.test',
        ]);

        $this->actingAs($user)
            ->get('/agency/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agency/Edit')
                ->where('agency.id', $agency->id)
                ->where('agency.name', 'Prefilled Agency')
                ->where('agency.email', 'prefilled@agency.test'));
    }

    public function test_unverified_agency_profile_update_applies_immediately(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->pendingVerification()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put('/agency', $this->validPayload())
            ->assertRedirect('/agency')
            ->assertSessionHas('success', __('app.agency_edit.flash'));

        $agency->refresh();

        $this->assertNull($agency->pendingChange);
        $this->assertSame('Updated Agency Name', $agency->name);
        $this->assertSame('updated@agency.test', $agency->email);
        $this->assertSame('+1 555 9999', $agency->phone);
        $this->assertSame('https://updated.test', $agency->website);
        $this->assertSame('99 New Street, Belgrade', $agency->address);
        $this->assertSame('Updated description of the agency.', $agency->description);
    }

    public function test_update_does_not_change_slug_status_or_approved_at(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        $originalSlug = $agency->slug;
        $originalStatus = $agency->status;
        $originalApprovedAt = $agency->approved_at;

        $this->actingAs($user)->put('/agency', $this->validPayload());

        $agency->refresh();

        $this->assertSame($originalSlug, $agency->slug);
        $this->assertSame($originalStatus, $agency->status);
        $this->assertEquals($originalApprovedAt, $agency->approved_at);
    }

    public function test_updating_approved_agency_stays_approved(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put('/agency', $this->validPayload());

        $this->assertTrue($agency->refresh()->isApproved());
    }

    public function test_updating_pending_agency_stays_pending(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->pendingVerification()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put('/agency', $this->validPayload());

        $this->assertTrue($agency->refresh()->isPendingVerification());
    }

    public function test_update_validation_fails_for_missing_required_fields(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put('/agency', ['name' => '', 'email' => ''])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_update_strips_unaccepted_fields(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->pendingVerification()->create(['user_id' => $user->id]);

        $originalSlug = $agency->slug;

        $this->actingAs($user)->put('/agency', $this->validPayload([
            'status' => 'approved',
            'slug' => 'hacked-slug',
            'approved_at' => now()->toDateTimeString(),
        ]));

        $agency->refresh();

        $this->assertTrue($agency->isPendingVerification());
        $this->assertSame($originalSlug, $agency->slug);
        $this->assertNull($agency->approved_at);
    }

    public function test_agency_user_without_record_is_redirected_to_setup(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)->get('/agency')->assertRedirect('/agency/setup');
        $this->actingAs($user)->get('/agency/edit')->assertRedirect('/agency/setup');
        $this->actingAs($user)->put('/agency', $this->validPayload())->assertRedirect('/agency/setup');
    }

    public function test_regular_user_gets_403(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->get('/agency')->assertForbidden();
        $this->actingAs($user)->get('/agency/edit')->assertForbidden();
        $this->actingAs($user)->put('/agency', $this->validPayload())->assertForbidden();
    }

    public function test_admin_gets_403(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/agency')->assertForbidden();
        $this->actingAs($admin)->get('/agency/edit')->assertForbidden();
        $this->actingAs($admin)->put('/agency', $this->validPayload())->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/agency')->assertRedirect('/login');
        $this->get('/agency/edit')->assertRedirect('/login');
        $this->put('/agency', $this->validPayload())->assertRedirect('/login');
    }

    public function test_suspended_agency_user_is_logged_out(): void
    {
        $user = User::factory()->agency()->suspended()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/agency')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_policy_denies_viewing_or_updating_another_agency(): void
    {
        $owner = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $owner->id]);

        $intruder = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $intruder->id]);

        $policy = new AgencyPolicy;

        $this->assertFalse($policy->view($intruder, $agency));
        $this->assertFalse($policy->update($intruder, $agency));
        $this->assertTrue($policy->view($owner, $agency));
        $this->assertTrue($policy->update($owner, $agency));
    }

    public function test_policy_denies_suspended_user(): void
    {
        $user = User::factory()->agency()->suspended()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        $policy = new AgencyPolicy;

        $this->assertFalse($policy->view($user, $agency));
        $this->assertFalse($policy->update($user, $agency));
    }
}
