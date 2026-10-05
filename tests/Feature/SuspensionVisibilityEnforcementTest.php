<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Agency;
use App\Models\Bookmark;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SuspensionVisibilityEnforcementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function accounts(): UserAccountService
    {
        return app(UserAccountService::class);
    }

    private function migration(): object
    {
        return require database_path(
            'migrations/2026_07_07_153126_revert_orphaned_hidden_listings_to_pending_review.php'
        );
    }

    // ---- Suspension hides public resources ------------------------------

    public function test_suspending_a_regular_user_hides_their_listings_from_the_listings_page(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 1));

        $this->accounts()->suspend($owner);

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));
    }

    public function test_suspending_a_regular_user_returns_404_on_their_listing_detail(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $viewer = User::factory()->regularUser()->create();

        $this->actingAs($viewer)->get("/listings/{$listing->slug}")->assertOk();

        $this->accounts()->suspend($owner);

        $this->actingAs($viewer)->get("/listings/{$listing->slug}")->assertNotFound();
    }

    public function test_suspending_an_agency_user_hides_the_agency_from_the_directory(): void
    {
        Notification::fake();

        $agency = Agency::factory()->approved()->create();

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page->has('agencies.data', 1));

        $this->accounts()->suspend($agency->user);

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page->has('agencies.data', 0));
    }

    public function test_suspending_an_agency_user_hides_agency_owned_listings(): void
    {
        Notification::fake();

        $agency = Agency::factory()->approved()->create();
        Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 1));

        $this->accounts()->suspend($agency->user);

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));
    }

    public function test_bookmarks_page_omits_suspended_owner_listings_without_deleting_the_record(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $bookmarker = User::factory()->regularUser()->create();
        Bookmark::factory()->create(['user_id' => $bookmarker->id, 'listing_id' => $listing->id]);

        $this->actingAs($bookmarker)->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page->has('listings', 1));

        $this->accounts()->suspend($owner);

        $this->actingAs($bookmarker)->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page->has('listings', 0));

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $bookmarker->id,
            'listing_id' => $listing->id,
        ]);
    }

    public function test_owner_dashboard_shows_own_listings_regardless_of_public_visibility(): void
    {
        $owner = User::factory()->regularUser()->create();
        Listing::factory()->hidden()->for($owner, 'owner')->create();

        $this->actingAs($owner)->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->has('listings', 1));
    }

    // ---- Suspension disables chat ---------------------------------------

    public function test_suspended_user_cannot_start_a_chat(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $buyer = User::factory()->regularUser()->create();

        $this->accounts()->suspend($buyer);

        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations")
            ->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_suspended_user_cannot_view_or_send_in_their_conversations(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->accounts()->suspend($buyer);

        $this->actingAs($buyer)->get("/conversations/{$conversation->id}")->assertForbidden();
        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Hi'])
            ->assertForbidden();
    }

    public function test_other_participant_cannot_view_or_send_when_the_counterparty_is_suspended(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->accounts()->suspend($owner);

        $this->actingAs($buyer)->get("/conversations/{$conversation->id}")->assertForbidden();
        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Hi'])
            ->assertForbidden();
    }

    // ---- Reactivation invariant (proof of Approach A) -------------------

    public function test_reactivation_restores_visibility_everywhere_without_mutating_resource_statuses(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $agency = Agency::factory()->approved()->create();
        $agencyListing = Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);

        $bookmarker = User::factory()->regularUser()->create();
        Bookmark::factory()->create(['user_id' => $bookmarker->id, 'listing_id' => $listing->id]);

        $buyer = User::factory()->regularUser()->create();
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $listingStatusBefore = $listing->status;
        $agencyStatusBefore = $agency->status;
        $agencyListingStatusBefore = $agencyListing->status;

        // Suspend both owners.
        $this->accounts()->suspend($owner);
        $this->accounts()->suspend($agency->user);

        // Everything is hidden.
        $this->get('/listings')->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));
        $this->get('/agencies')->assertInertia(fn (Assert $page) => $page->has('agencies.data', 0));
        $this->actingAs($buyer)->get("/listings/{$listing->slug}")->assertNotFound();
        $this->actingAs($bookmarker)->get('/bookmarks')->assertInertia(fn (Assert $page) => $page->has('listings', 0));
        $this->actingAs($buyer)->get("/conversations/{$conversation->id}")->assertForbidden();

        // Reactivate both owners.
        $this->accounts()->reactivate($owner->fresh());
        $this->accounts()->reactivate($agency->user->fresh());

        // Everything is visible again.
        $this->get('/listings')->assertInertia(fn (Assert $page) => $page->has('listings.data', 2));
        $this->get('/agencies')->assertInertia(fn (Assert $page) => $page->has('agencies.data', 1));
        $this->actingAs($buyer)->get("/listings/{$listing->slug}")->assertOk();
        $this->actingAs($bookmarker)->get('/bookmarks')->assertInertia(fn (Assert $page) => $page->has('listings', 1));
        $this->actingAs($buyer)->get("/conversations/{$conversation->id}")->assertOk();

        // No resource status was ever mutated — only the users' statuses changed.
        $this->assertSame($listingStatusBefore, $listing->fresh()->status);
        $this->assertSame($agencyStatusBefore, $agency->fresh()->status);
        $this->assertSame($agencyListingStatusBefore, $agencyListing->fresh()->status);
    }

    // ---- Data migration: revert orphaned-hidden listings ----------------

    public function test_migration_reverts_orphaned_hidden_listing_to_pending_review(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->hidden()->for($owner, 'owner')->create([
            'approved_at' => now()->subDay(),
            'rejection_reason' => null,
        ]);

        $this->migration()->up();

        $listing->refresh();

        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
        $this->assertNull($listing->rejection_reason);
    }

    public function test_migration_reverts_orphaned_hidden_agency_listing(): void
    {
        $agency = Agency::factory()->approved()->create();
        $listing = Listing::factory()->hidden()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);

        $this->migration()->up();

        $this->assertSame(ListingStatus::PendingReview, $listing->fresh()->status);
    }

    public function test_migration_leaves_hidden_listing_when_owner_is_still_suspended(): void
    {
        $owner = User::factory()->suspended()->create();
        $listing = Listing::factory()->hidden()->for($owner, 'owner')->create();

        $this->migration()->up();

        $this->assertSame(ListingStatus::Hidden, $listing->fresh()->status);
    }

    public function test_migration_is_idempotent(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->hidden()->for($owner, 'owner')->create();

        $this->migration()->up();
        $revertedAt = $listing->fresh()->updated_at;

        $this->travel(5)->minutes();
        $this->migration()->up();

        $listing->refresh();
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertTrue($listing->updated_at->equalTo($revertedAt));
    }

    public function test_migration_down_is_a_noop(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->hidden()->for($owner, 'owner')->create();

        $this->migration()->down();

        $this->assertSame(ListingStatus::Hidden, $listing->fresh()->status);
    }

    public function test_migration_logs_the_reverted_listing_ids(): void
    {
        Log::spy();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->hidden()->for($owner, 'owner')->create();

        $this->migration()->up();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($listing) {
                return str_contains($message, 'orphaned-hidden')
                    && in_array($listing->id, $context['listing_ids'], true);
            });
    }
}
