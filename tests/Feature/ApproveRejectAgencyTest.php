<?php

namespace Tests\Feature;

use App\Enums\AgencyStatus;
use App\Exceptions\InvalidAgencyStatusTransitionException;
use App\Models\Agency;
use App\Models\AgencyProfileChange;
use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\AgencyApprovedNotification;
use App\Notifications\AgencyRejectedNotification;
use App\Services\AgencyService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApproveRejectAgencyTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function pendingAgency(): Agency
    {
        return Agency::factory()->pendingVerification()->create();
    }

    public function test_admin_can_view_the_moderation_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/agencies')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Agencies/Index'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/agencies')->assertRedirect('/login');
    }

    public function test_regular_user_gets_403(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/admin/agencies')
            ->assertForbidden();
    }

    public function test_agency_user_gets_403(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->get('/admin/agencies')
            ->assertForbidden();
    }

    public function test_only_pending_agencies_appear_in_the_queue(): void
    {
        $pending = $this->pendingAgency();
        Agency::factory()->approved()->create();
        Agency::factory()->rejected()->create();
        Agency::factory()->hidden()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $pending->id)
            );
    }

    public function test_queue_is_ordered_newest_first(): void
    {
        $older = $this->pendingAgency();
        $older->forceFill(['created_at' => now()->subDays(3)])->save();

        $newer = $this->pendingAgency();
        $newer->forceFill(['created_at' => now()->subDay()])->save();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 2)
                ->where('agencies.data.0.id', $newer->id)
                ->where('agencies.data.1.id', $older->id)
            );
    }

    public function test_moderation_payload_includes_owner_data(): void
    {
        $owner = User::factory()->agency()->create(['name' => 'Acme Owner', 'email' => 'acme@example.test']);
        $agency = Agency::factory()->pendingVerification()->for($owner, 'user')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agencies.data.0.id', $agency->id)
                ->where('agencies.data.0.ownerName', 'Acme Owner')
                ->where('agencies.data.0.ownerEmail', 'acme@example.test')
            );
    }

    public function test_admin_can_approve_a_pending_agency(): void
    {
        Notification::fake();

        $agency = $this->pendingAgency();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/agencies/{$agency->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('success');

        $agency->refresh();

        $this->assertSame(AgencyStatus::Approved, $agency->status);
        $this->assertNotNull($agency->approved_at);
        $this->assertNull($agency->rejection_reason);

        Notification::assertSentTo($agency->user, AgencyApprovedNotification::class);
    }

    public function test_admin_can_reject_a_pending_agency(): void
    {
        Notification::fake();

        $agency = $this->pendingAgency();
        $owner = $agency->user;

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/agencies/{$agency->id}/reject", ['reason' => 'Incomplete agency details.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull(Agency::find($agency->id));
        $this->assertFalse(Agency::whereKey($agency->id)->exists());

        Notification::assertSentTo($owner, AgencyRejectedNotification::class);
    }

    public function test_rejecting_an_agency_cascades_to_listings_and_bookmarks(): void
    {
        $agency = $this->pendingAgency();

        $listing = Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);

        $bookmark = Bookmark::factory()->create([
            'user_id' => User::factory()->regularUser(),
            'listing_id' => $listing->id,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/agencies/{$agency->id}/reject", ['reason' => 'Spam agency.']);

        $this->assertFalse(Agency::whereKey($agency->id)->exists());
        $this->assertFalse(Listing::withTrashed()->whereKey($listing->id)->exists());
        $this->assertFalse(Bookmark::whereKey($bookmark->id)->exists());
    }

    public function test_rejected_agency_disappears_from_the_queue(): void
    {
        $agency = $this->pendingAgency();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post("/admin/agencies/{$agency->id}/reject", ['reason' => 'Not acceptable.']);

        $this->actingAs($admin)
            ->get('/admin/agencies')
            ->assertInertia(fn (Assert $page) => $page->has('agencies.data', 0));
    }

    public function test_approving_a_non_pending_agency_returns_403(): void
    {
        $agency = Agency::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/agencies/{$agency->id}/approve")
            ->assertForbidden();
    }

    public function test_rejecting_a_non_pending_agency_returns_403(): void
    {
        $agency = Agency::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/agencies/{$agency->id}/reject", ['reason' => 'A valid reason.'])
            ->assertForbidden();
    }

    public function test_regular_user_cannot_approve(): void
    {
        $agency = $this->pendingAgency();

        $this->actingAs(User::factory()->regularUser()->create())
            ->post("/admin/agencies/{$agency->id}/approve")
            ->assertForbidden();
    }

    public function test_reject_without_a_reason_fails_validation(): void
    {
        $agency = $this->pendingAgency();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/agencies/{$agency->id}/reject", ['reason' => '   '])
            ->assertSessionHasErrors('reason');

        $this->assertSame(AgencyStatus::PendingVerification, $agency->refresh()->status);
    }

    public function test_reject_with_reason_over_1000_chars_fails_validation(): void
    {
        $agency = $this->pendingAgency();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/agencies/{$agency->id}/reject", ['reason' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('reason');

        $this->assertSame(AgencyStatus::PendingVerification, $agency->refresh()->status);
    }

    public function test_service_approve_throws_on_non_pending_agency(): void
    {
        $agency = Agency::factory()->approved()->create();

        $this->expectException(InvalidAgencyStatusTransitionException::class);

        app(AgencyService::class)->approve($agency);
    }

    public function test_service_reject_throws_on_non_pending_agency(): void
    {
        $agency = Agency::factory()->hidden()->create();

        $this->expectException(InvalidAgencyStatusTransitionException::class);

        app(AgencyService::class)->reject($agency, 'A reason.');
    }

    public function test_approve_catches_transition_exception_and_flashes_error(): void
    {
        $agency = $this->pendingAgency();

        $this->mock(AgencyService::class, function ($mock) {
            $mock->shouldReceive('approve')
                ->andThrow(new InvalidAgencyStatusTransitionException('race'));
        });

        $this->actingAs(User::factory()->admin()->create())
            ->from('/admin/agencies')
            ->post("/admin/agencies/{$agency->id}/approve")
            ->assertRedirect('/admin/agencies')
            ->assertSessionHas('error');
    }

    public function test_the_agency_queue_query_count_does_not_grow_with_agencies_and_changes(): void
    {
        $admin = User::factory()->admin()->create();

        $countQueries = function () use ($admin): int {
            $actor = $admin->fresh();
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($actor)->get('/admin/agencies')->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $seed = function (): void {
            Agency::factory()->pendingVerification()->create();
            AgencyProfileChange::factory()->create();
        };

        $seed();
        $seed();
        $withTwo = $countQueries();

        foreach (range(1, 4) as $index) {
            $seed();
        }

        $this->assertSame(6, AgencyProfileChange::count());
        $this->assertSame($withTwo, $countQueries());
    }
}
