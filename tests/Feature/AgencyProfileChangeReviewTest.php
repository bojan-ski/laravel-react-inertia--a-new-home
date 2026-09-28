<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AgencyProfileChange;
use App\Models\User;
use App\Notifications\AgencyChangesApprovedNotification;
use App\Notifications\AgencyChangesRejectedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencyProfileChangeReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array{User, Agency}
     */
    private function approvedAgency(): array
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create([
            'user_id' => $user->id,
            'name' => 'Harbor Homes',
            'legal_name' => 'Harbor Homes LLC',
            'vat_id' => 'DE123456789',
            'registration_number' => '01234567',
            'registration_country' => 'DE',
            'email' => 'hello@harbor.test',
            'phone' => '+1 555 0100',
            'website' => 'https://harbor.test',
            'address' => '1 Pier Road',
            'description' => 'Coastal homes.',
        ]);

        return [$user, $agency];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadFor(Agency $agency, array $overrides = []): array
    {
        return array_merge([
            'name' => $agency->name,
            'legal_name' => $agency->legal_name,
            'vat_id' => $agency->vat_id,
            'registration_number' => $agency->registration_number,
            'registration_country' => $agency->registration_country->value,
            'broker_register_number' => $agency->broker_register_number,
            'email' => $agency->email,
            'phone' => $agency->phone,
            'website' => $agency->website,
            'address' => $agency->address,
            'description' => $agency->description,
        ], $overrides);
    }

    public function test_editing_an_approved_agency_submits_changes_instead_of_applying_them(): void
    {
        [$user, $agency] = $this->approvedAgency();

        $this->actingAs($user)
            ->put('/agency', $this->payloadFor($agency, ['name' => 'Harbor Homes & Co', 'phone' => '+1 555 0199']))
            ->assertRedirect('/agency')
            ->assertSessionHas('success', __('app.agency_edit.flash_submitted'));

        $agency->refresh();
        $this->assertSame('Harbor Homes', $agency->name);
        $this->assertSame('+1 555 0100', $agency->phone);
        $this->assertSame(['name' => 'Harbor Homes & Co', 'phone' => '+1 555 0199'], $agency->pendingChange->proposed_values);
    }

    public function test_public_page_keeps_showing_the_approved_profile(): void
    {
        [$user, $agency] = $this->approvedAgency();

        $this->actingAs($user)->put('/agency', $this->payloadFor($agency, ['name' => 'Renamed Agency']));

        $this->get("/agencies/{$agency->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('agency.name', 'Harbor Homes'));
    }

    public function test_saving_unchanged_values_submits_nothing_and_withdraws_a_pending_request(): void
    {
        [$user, $agency] = $this->approvedAgency();
        $this->actingAs($user)->put('/agency', $this->payloadFor($agency, ['name' => 'Renamed Agency']));
        $this->assertNotNull($agency->fresh()->pendingChange);

        $this->actingAs($user)
            ->put('/agency', $this->payloadFor($agency->fresh()))
            ->assertSessionHas('success', __('app.agency_edit.flash_unchanged'));

        $this->assertDatabaseCount('agency_profile_changes', 0);
    }

    public function test_resubmitting_replaces_the_pending_request(): void
    {
        [$user, $agency] = $this->approvedAgency();

        $this->actingAs($user)->put('/agency', $this->payloadFor($agency, ['name' => 'First Try']));
        $this->actingAs($user)->put('/agency', $this->payloadFor($agency, ['description' => 'Second try.']));

        $this->assertDatabaseCount('agency_profile_changes', 1);
        $this->assertSame(['description' => 'Second try.'], $agency->fresh()->pendingChange->proposed_values);
    }

    public function test_pending_changes_are_encrypted_at_rest(): void
    {
        [$user, $agency] = $this->approvedAgency();

        $this->actingAs($user)->put('/agency', $this->payloadFor($agency, ['legal_name' => 'Confidential Holdings GmbH']));

        $raw = DB::table('agency_profile_changes')->value('proposed_values');
        $this->assertStringNotContainsString('Confidential Holdings', $raw);
    }

    public function test_migration_strips_us_fields_from_pending_changes_and_drops_emptied_rows(): void
    {
        [, $mixed] = $this->approvedAgency();
        [, $usOnly] = $this->approvedAgency();
        AgencyProfileChange::factory()->for($mixed)->create([
            'proposed_values' => ['name' => 'Renamed Agency', 'ein' => '98-7654321', 'license_state' => 'NV'],
        ]);
        AgencyProfileChange::factory()->for($usOnly)->create([
            'proposed_values' => ['license_number' => '0999', 'license_state' => 'TX'],
        ]);

        $migration = require database_path('migrations/2026_09_28_160106_strip_us_business_fields_from_agency_profile_changes.php');
        $migration->up();

        $this->assertSame(['name' => 'Renamed Agency'], $mixed->fresh()->pendingChange->proposed_values);
        $this->assertNull($usOnly->fresh()->pendingChange);
        $this->assertDatabaseCount('agency_profile_changes', 1);
    }

    public function test_owner_sees_the_pending_request_on_dashboard_and_edit_page(): void
    {
        [$user, $agency] = $this->approvedAgency();
        $this->actingAs($user)->put('/agency', $this->payloadFor($agency, ['name' => 'Renamed Agency']));

        $this->actingAs($user->refresh())
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('pendingChange.changes.name', 'Renamed Agency')
                ->has('pendingChange.submittedAt'));

        $this->actingAs($user)
            ->get('/agency/edit')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agency.name', 'Harbor Homes')
                ->where('pendingChange.changes.name', 'Renamed Agency'));
    }

    public function test_admin_sees_a_diff_of_the_pending_changes(): void
    {
        [$user, $agency] = $this->approvedAgency();
        $this->actingAs($user)->put('/agency', $this->payloadFor($agency, ['name' => 'Renamed Agency', 'registration_country' => 'AT']));

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->has('profileChanges', 1)
                ->where('profileChanges.0.agencyId', $agency->id)
                ->where('profileChanges.0.changes', [
                    ['field' => 'name', 'current' => 'Harbor Homes', 'proposed' => 'Renamed Agency'],
                    ['field' => 'registration_country', 'current' => 'DE', 'proposed' => 'AT'],
                ])
                ->where('fieldLabels.name', __('app.agency_edit.fields.name')));
    }

    public function test_admin_can_approve_changes(): void
    {
        Notification::fake();
        [$user, $agency] = $this->approvedAgency();
        $this->actingAs($user)->put('/agency', $this->payloadFor($agency, ['name' => 'Renamed Agency', 'website' => '']));

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/agencies/{$agency->id}/changes/approve")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.admin.agencies.changes.flash.approved'));

        $agency->refresh();
        $this->assertSame('Renamed Agency', $agency->name);
        $this->assertNull($agency->website);
        $this->assertTrue($agency->isApproved());
        $this->assertNull($agency->pendingChange);
        Notification::assertSentTo($user, AgencyChangesApprovedNotification::class);
    }

    public function test_approving_keeps_the_slug_stable(): void
    {
        [$user, $agency] = $this->approvedAgency();
        $slug = $agency->slug;
        $this->actingAs($user)->put('/agency', $this->payloadFor($agency, ['name' => 'Renamed Agency']));

        $this->actingAs(User::factory()->admin()->create())->post("/admin/agencies/{$agency->id}/changes/approve");

        $this->assertSame($slug, $agency->fresh()->slug);
    }

    public function test_admin_can_reject_changes_with_a_reason(): void
    {
        Notification::fake();
        [$user, $agency] = $this->approvedAgency();
        $this->actingAs($user)->put('/agency', $this->payloadFor($agency, ['name' => 'Renamed Agency']));

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/agencies/{$agency->id}/changes/reject", ['reason' => 'Name does not match the license.'])
            ->assertRedirect()
            ->assertSessionHas('success', __('app.admin.agencies.changes.flash.rejected'));

        $agency->refresh();
        $this->assertModelExists($agency);
        $this->assertSame('Harbor Homes', $agency->name);
        $this->assertTrue($agency->isApproved());
        $this->assertNull($agency->pendingChange);
        Notification::assertSentTo(
            $user,
            AgencyChangesRejectedNotification::class,
            fn (AgencyChangesRejectedNotification $notification): bool => $notification->reason === 'Name does not match the license.',
        );
    }

    public function test_rejecting_changes_requires_a_reason(): void
    {
        $change = AgencyProfileChange::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/agencies/{$change->agency_id}/changes/reject", ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertModelExists($change);
    }

    public function test_reviewing_without_a_pending_request_is_forbidden(): void
    {
        [, $agency] = $this->approvedAgency();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post("/admin/agencies/{$agency->id}/changes/approve")->assertForbidden();
        $this->actingAs($admin)->post("/admin/agencies/{$agency->id}/changes/reject", ['reason' => 'x'])->assertForbidden();
    }

    public function test_non_admins_cannot_review_changes(): void
    {
        $change = AgencyProfileChange::factory()->create();

        foreach ([User::factory()->regularUser()->create(), $change->agency->user] as $user) {
            $this->actingAs($user)->post("/admin/agencies/{$change->agency_id}/changes/approve")->assertForbidden();
            $this->actingAs($user)->post("/admin/agencies/{$change->agency_id}/changes/reject", ['reason' => 'x'])->assertForbidden();
        }

        $this->assertModelExists($change);
    }

    public function test_deleting_the_agency_removes_its_pending_request(): void
    {
        $change = AgencyProfileChange::factory()->create();

        $change->agency->delete();

        $this->assertModelMissing($change);
    }

    public function test_dashboard_counts_pending_changes(): void
    {
        AgencyProfileChange::factory()->count(2)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page->where('stats.pendingAgencyChanges', 2));
    }
}
