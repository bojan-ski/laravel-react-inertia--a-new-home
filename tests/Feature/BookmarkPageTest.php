<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_page_receives_the_heart_remove_label(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->where('translations.remove', 'Remove from bookmarks'));
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

    public function test_closed_bookmarks_are_listed_in_their_own_group(): void
    {
        $user = User::factory()->regularUser()->create();

        $active = Listing::factory()->approved()->create();
        $sold = Listing::factory()->sold()->create();
        $rented = Listing::factory()->rented()->create();

        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $active->id]);
        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $sold->id, 'created_at' => now()->subDay()]);
        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $rented->id, 'created_at' => now()->subDays(2)]);

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 1)
                ->where('listings.0.id', $active->id)
                ->missing('listings.0.status')
                ->has('closedListings', 2)
                ->where('closedListings.0.id', $sold->id)
                ->where('closedListings.0.status', 'sold')
                ->where('closedListings.0.isBookmarked', true)
                ->where('closedListings.0.canBookmark', true)
                ->where('closedListings.1.id', $rented->id)
                ->where('closedListings.1.status', 'rented')
                ->where('translations.closed.heading', __('app.bookmarks.closed.heading'))
            );
    }

    public function test_closed_bookmarks_of_suspended_owners_and_trashed_listings_are_excluded(): void
    {
        $user = User::factory()->regularUser()->create();

        $suspendedOwned = Listing::factory()->sold()->for(User::factory()->suspended(), 'owner')->create();
        $trashed = Listing::factory()->rented()->create();

        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $suspendedOwned->id]);
        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $trashed->id]);

        $trashed->delete();

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 0)
                ->has('closedListings', 0)
            );
    }

    public function test_closed_bookmarks_do_not_add_a_query_per_card(): void
    {
        $user = User::factory()->regularUser()->create();

        $countQueries = function () use ($user): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($user)->get('/bookmarks')->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => Listing::factory()->sold()->create()->id]);

        $withOne = $countQueries();

        foreach (Listing::factory()->count(3)->rented()->create() as $listing) {
            Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $listing->id]);
        }

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->has('closedListings', 4)
                ->where('closedListings.0.canBookmark', true)
                ->where('closedListings.3.canBookmark', true)
            );

        $this->assertSame($withOne, $countQueries());
    }

    public function test_closed_bookmark_can_be_removed_with_the_heart(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->sold()->create();
        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.bookmarks.flash_removed'));

        $this->assertDatabaseMissing('bookmarks', ['user_id' => $user->id, 'listing_id' => $listing->id]);
    }

    public function test_closed_listing_cannot_be_bookmarked_by_a_user_without_a_bookmark(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->rented()->create();
        Bookmark::factory()->create(['listing_id' => $listing->id]);

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertNotFound();

        $this->assertDatabaseMissing('bookmarks', ['user_id' => $user->id, 'listing_id' => $listing->id]);
        $this->assertDatabaseCount('bookmarks', 1);
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
