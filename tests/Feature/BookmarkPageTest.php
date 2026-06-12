<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookmarkPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_view_bookmarks_page(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/bookmarks')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Bookmarks/Index'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/bookmarks')->assertRedirect('/login');
    }

    public function test_page_shows_only_the_current_users_bookmarks(): void
    {
        $user = User::factory()->regularUser()->create();
        $other = User::factory()->regularUser()->create();

        $mine = Listing::factory()->approved()->create();
        $theirs = Listing::factory()->approved()->create();

        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $mine->id]);
        Bookmark::factory()->create(['user_id' => $other->id, 'listing_id' => $theirs->id]);

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 1)
                ->where('listings.0.id', $mine->id)
            );
    }

    public function test_pending_rejected_hidden_and_soft_deleted_listings_are_excluded(): void
    {
        $user = User::factory()->regularUser()->create();

        $visible = Listing::factory()->approved()->create();
        $pending = Listing::factory()->pendingReview()->create();
        $rejected = Listing::factory()->rejected()->create();
        $hidden = Listing::factory()->hidden()->create();
        $deleted = Listing::factory()->approved()->create();

        foreach ([$visible, $pending, $rejected, $hidden, $deleted] as $listing) {
            Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $listing->id]);
        }

        $deleted->delete();

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 1)
                ->where('listings.0.id', $visible->id)
            );
    }

    public function test_suspended_owner_listings_are_excluded(): void
    {
        $user = User::factory()->regularUser()->create();

        $visible = Listing::factory()->approved()->create();
        $suspendedOwned = Listing::factory()->approved()->for(User::factory()->suspended(), 'owner')->create();

        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $visible->id]);
        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $suspendedOwned->id]);

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 1)
                ->where('listings.0.id', $visible->id)
            );
    }

    public function test_ordering_is_by_bookmark_created_at_descending(): void
    {
        $user = User::factory()->regularUser()->create();

        $older = Listing::factory()->approved()->create();
        $newer = Listing::factory()->approved()->create();

        Bookmark::factory()->create([
            'user_id' => $user->id,
            'listing_id' => $older->id,
            'created_at' => now()->subDays(2),
        ]);
        Bookmark::factory()->create([
            'user_id' => $user->id,
            'listing_id' => $newer->id,
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.0.id', $newer->id)
                ->where('listings.1.id', $older->id)
            );
    }

    public function test_empty_state_renders_when_no_visible_bookmarks(): void
    {
        $user = User::factory()->regularUser()->create();
        $hiddenBookmark = Listing::factory()->pendingReview()->create();
        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $hiddenBookmark->id]);

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page->has('listings', 0));
    }

    public function test_listings_index_reflects_per_listing_bookmark_state_for_authenticated_users(): void
    {
        $user = User::factory()->regularUser()->create();

        $bookmarked = Listing::factory()->approved()->create(['approved_at' => now()->subDay()]);
        $notBookmarked = Listing::factory()->approved()->create(['approved_at' => now()->subDays(10)]);

        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $bookmarked->id]);

        $this->actingAs($user)
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $bookmarked->id)
                ->where('listings.data.0.isBookmarked', true)
                ->where('listings.data.1.id', $notBookmarked->id)
                ->where('listings.data.1.isBookmarked', false)
            );
    }

    public function test_listings_index_bookmark_state_is_false_for_guests(): void
    {
        Listing::factory()->approved()->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('listings.data.0.isBookmarked', false));
    }

    public function test_detail_page_is_bookmarked_prop_reflects_actual_user_state(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create();
        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->actingAs($user)
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('isBookmarked', true));
    }
}
