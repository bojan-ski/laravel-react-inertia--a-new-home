<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingApprovedNotification;
use App\Notifications\ListingRejectedNotification;
use App\Services\ListingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApproveRejectListingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function pendingListing(): Listing
    {
        return Listing::factory()
            ->for(User::factory()->regularUser(), 'owner')
            ->pendingReview()
            ->create();
    }

    public function test_admin_can_view_the_moderation_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Listings/Index'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/listings')->assertRedirect('/login');
    }

    public function test_regular_user_gets_403(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/admin/listings')
            ->assertForbidden();
    }

    public function test_agency_user_gets_403(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->get('/admin/listings')
            ->assertForbidden();
    }

    public function test_only_pending_listings_appear_in_the_queue(): void
    {
        $pending = $this->pendingListing();
        Listing::factory()->approved()->create();
        Listing::factory()->hidden()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 1)
                ->where('listings.0.id', $pending->id)
            );
    }

    public function test_rejected_and_soft_deleted_listings_are_excluded(): void
    {
        $rejected = Listing::factory()->rejected()->create();
        $rejected->delete();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/listings')
            ->assertInertia(fn (Assert $page) => $page->has('listings', 0));
    }

    public function test_queue_is_ordered_newest_first(): void
    {
        $older = $this->pendingListing();
        $older->forceFill(['created_at' => now()->subDays(3)])->save();

        $newer = $this->pendingListing();
        $newer->forceFill(['created_at' => now()->subDay()])->save();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 2)
                ->where('listings.0.id', $newer->id)
                ->where('listings.1.id', $older->id)
            );
    }

    public function test_moderation_payload_includes_owner_data(): void
    {
        $owner = User::factory()->regularUser()->create(['name' => 'Jane Doe', 'email' => 'jane@example.test']);
        $listing = Listing::factory()->for($owner, 'owner')->pendingReview()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.0.ownerName', 'Jane Doe')
                ->where('listings.0.ownerEmail', 'jane@example.test')
                ->where('listings.0.id', $listing->id)
            );
    }

    public function test_admin_can_approve_a_pending_listing(): void
    {
        Notification::fake();

        $listing = $this->pendingListing();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('success');

        $listing->refresh();

        $this->assertSame(ListingStatus::Approved, $listing->status);
        $this->assertNotNull($listing->approved_at);
        $this->assertNull($listing->rejection_reason);
        $this->assertNull($listing->rejected_at);
        $this->assertTrue(Listing::actuallyPubliclyVisible()->whereKey($listing->id)->exists());

        Notification::assertSentTo($listing->owner, ListingApprovedNotification::class);
    }

    public function test_admin_can_reject_a_pending_listing(): void
    {
        Notification::fake();

        $listing = $this->pendingListing();
        $owner = $listing->owner;

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/reject", ['reason' => 'Photos do not match the description.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $listing = Listing::withTrashed()->find($listing->id);

        $this->assertSame(ListingStatus::Rejected, $listing->status);
        $this->assertSame('Photos do not match the description.', $listing->rejection_reason);
        $this->assertNotNull($listing->rejected_at);
        $this->assertNull($listing->approved_at);
        $this->assertNotNull($listing->deleted_at);

        $this->assertFalse(Listing::whereKey($listing->id)->exists());

        Notification::assertSentTo($owner, ListingRejectedNotification::class);
    }

    public function test_rejected_listing_disappears_from_the_queue(): void
    {
        $listing = $this->pendingListing();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post("/admin/listings/{$listing->id}/reject", ['reason' => 'Not acceptable.']);

        $this->actingAs($admin)
            ->get('/admin/listings')
            ->assertInertia(fn (Assert $page) => $page->has('listings', 0));
    }

    public function test_approving_a_non_pending_listing_returns_403(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertForbidden();
    }

    public function test_rejecting_a_non_pending_listing_returns_403(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/reject", ['reason' => 'A valid reason.'])
            ->assertForbidden();
    }

    public function test_regular_user_cannot_approve(): void
    {
        $listing = $this->pendingListing();

        $this->actingAs(User::factory()->regularUser()->create())
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertForbidden();
    }

    public function test_reject_without_a_reason_fails_validation(): void
    {
        $listing = $this->pendingListing();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/reject", ['reason' => '   '])
            ->assertSessionHasErrors('reason');

        $this->assertSame(ListingStatus::PendingReview, $listing->refresh()->status);
    }

    public function test_reject_with_reason_over_1000_chars_fails_validation(): void
    {
        $listing = $this->pendingListing();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/reject", ['reason' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('reason');

        $this->assertSame(ListingStatus::PendingReview, $listing->refresh()->status);
    }

    public function test_service_approve_throws_on_non_pending_listing(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->expectException(InvalidListingStatusTransitionException::class);

        app(ListingService::class)->approve($listing);
    }

    public function test_service_reject_throws_on_non_pending_listing(): void
    {
        $listing = Listing::factory()->hidden()->create();

        $this->expectException(InvalidListingStatusTransitionException::class);

        app(ListingService::class)->reject($listing, 'A reason.');
    }

    public function test_approve_catches_transition_exception_and_flashes_error(): void
    {
        $listing = $this->pendingListing();

        $this->mock(ListingService::class, function ($mock) {
            $mock->shouldReceive('approve')
                ->andThrow(new InvalidListingStatusTransitionException('race'));
        });

        $this->actingAs(User::factory()->admin()->create())
            ->from('/admin/listings')
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertRedirect('/admin/listings')
            ->assertSessionHas('error');
    }
}
