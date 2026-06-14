<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DeleteListingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_delete_their_pending_listing(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->delete("/my-listings/{$listing->id}")
            ->assertRedirect('/profile')
            ->assertSessionHas('success', __('app.profile.flash_deleted'));

        $this->assertSoftDeleted($listing);
    }

    public function test_owner_can_delete_their_approved_listing(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($user, 'owner')->create();

        $this->actingAs($user)->delete("/my-listings/{$listing->id}")->assertRedirect('/profile');

        $this->assertSoftDeleted($listing);
    }

    public function test_owner_can_delete_their_hidden_listing(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->hidden()->for($user, 'owner')->create();

        $this->actingAs($user)->delete("/my-listings/{$listing->id}")->assertRedirect('/profile');

        $this->assertSoftDeleted($listing);
    }

    public function test_owner_can_delete_their_rejected_listing(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->rejected()->for($user, 'owner')->create();

        $this->actingAs($user)->delete("/my-listings/{$listing->id}")->assertRedirect('/profile');

        $this->assertSoftDeleted($listing);
    }

    public function test_owner_cannot_delete_another_users_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $other = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs($other)->delete("/my-listings/{$listing->id}")->assertForbidden();

        $this->assertNotSoftDeleted($listing);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->delete("/my-listings/{$listing->id}")->assertRedirect('/login');

        $this->assertNotSoftDeleted($listing);
    }

    public function test_agency_user_cannot_use_this_route(): void
    {
        $agency = User::factory()->agency()->create();
        $listing = Listing::factory()->approved()->create();

        $this->actingAs($agency)->delete("/my-listings/{$listing->id}")->assertForbidden();

        $this->assertNotSoftDeleted($listing);
    }

    public function test_admin_user_cannot_use_this_route(): void
    {
        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->approved()->create();

        $this->actingAs($admin)->delete("/my-listings/{$listing->id}")->assertForbidden();

        $this->assertNotSoftDeleted($listing);
    }

    public function test_delete_soft_deletes_and_keeps_the_row_in_the_database(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($user, 'owner')->create();

        $this->actingAs($user)->delete("/my-listings/{$listing->id}");

        $trashed = Listing::withTrashed()->find($listing->id);

        $this->assertNotNull($trashed);
        $this->assertNotNull($trashed->deleted_at);
        $this->assertSame($listing->status, $trashed->status);
        $this->assertSame($user->id, $trashed->user_id);
    }

    public function test_deleted_listing_no_longer_appears_on_the_dashboard(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($user, 'owner')->create();

        $this->actingAs($user)->delete("/my-listings/{$listing->id}");

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Index')
                ->has('listings', 0)
            );
    }

    public function test_deleted_listing_no_longer_appears_on_the_public_listings_page(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($user, 'owner')->create();

        $this->actingAs($user)->delete("/my-listings/{$listing->id}");

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Index')
                ->has('listings.data', 0)
            );
    }

    public function test_bookmarks_pointing_at_the_deleted_listing_become_invisible(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $bookmarker = User::factory()->regularUser()->create();
        $bookmark = Bookmark::factory()->create([
            'user_id' => $bookmarker->id,
            'listing_id' => $listing->id,
        ]);

        $this->actingAs($owner)->delete("/my-listings/{$listing->id}");

        $this->actingAs($bookmarker)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookmarks/Index')
                ->has('listings', 0)
            );

        $this->assertDatabaseHas('bookmarks', ['id' => $bookmark->id]);
    }
}
