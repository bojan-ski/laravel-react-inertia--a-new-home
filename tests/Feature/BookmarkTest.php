<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
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
            ->assertNotFound();

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_admin_cannot_bookmark(): void
    {
        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->approved()->create();

        $this->actingAs($admin)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertNotFound();

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_suspended_user_cannot_bookmark(): void
    {
        $user = User::factory()->suspended()->create();
        $listing = Listing::factory()->approved()->create();

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertNotFound();

        $this->assertDatabaseCount('bookmarks', 0);
    }
}
