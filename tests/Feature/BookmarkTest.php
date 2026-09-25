<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_bookmark_an_approved_listing(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create();

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);
    }

    public function test_authenticated_user_can_unbookmark_a_previously_bookmarked_listing(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create();
        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);
    }

    public function test_duplicate_bookmarks_are_prevented_at_the_database_level(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create();

        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->expectException(QueryException::class);

        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $listing->id]);
    }

    public function test_guest_cannot_toggle_and_is_redirected_to_login(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->post("/listings/{$listing->id}/bookmark")->assertRedirect('/login');

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_bookmark_of_a_non_public_listing_returns_404(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->create();

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertNotFound();

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_owner_cannot_bookmark_their_own_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertForbidden();

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_admin_cannot_bookmark(): void
    {
        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->approved()->create();

        $this->actingAs($admin)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertForbidden();

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_suspended_user_cannot_bookmark(): void
    {
        $user = User::factory()->suspended()->create();
        $listing = Listing::factory()->approved()->create();

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertForbidden();

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_listings_index_hides_bookmark_on_own_listings_only(): void
    {
        $owner = User::factory()->regularUser()->create();
        $own = Listing::factory()->approved()->for($owner, 'owner')->create(['approved_at' => now()->subDay()]);
        $other = Listing::factory()->approved()->create(['approved_at' => now()->subDays(2)]);

        $this->actingAs($owner)
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $own->id)
                ->where('listings.data.0.canBookmark', false)
                ->where('listings.data.1.id', $other->id)
                ->where('listings.data.1.canBookmark', true));
    }

    public function test_listings_index_shows_bookmark_to_guests(): void
    {
        Listing::factory()->approved()->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('listings.data.0.canBookmark', true));
    }

    public function test_listings_index_hides_bookmark_from_admins(): void
    {
        Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('listings.data.0.canBookmark', false));
    }

    public function test_detail_page_hides_bookmark_on_own_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('canBookmark', false));
    }

    public function test_detail_page_shows_bookmark_to_other_users(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('canBookmark', true));
    }
}
