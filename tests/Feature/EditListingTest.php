<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EditListingTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Updated City Apartment',
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => 310000,
            'currency' => 'EUR',
            'description' => 'An updated description for the apartment.',
            'country' => 'Serbia',
            'city' => 'Belgrade',
        ], $overrides);
    }

    public function test_owner_can_view_the_edit_page_for_their_listing(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->get("/my-listings/{$listing->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Edit')
                ->where('listing.id', $listing->id)
            );
    }

    public function test_owner_cannot_view_edit_page_for_another_users_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $other = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs($other)->get("/my-listings/{$listing->id}/edit")->assertForbidden();
        $this->actingAs($other)->put("/my-listings/{$listing->id}", $this->validPayload())->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->get("/my-listings/{$listing->id}/edit")->assertRedirect('/login');
        $this->put("/my-listings/{$listing->id}", $this->validPayload())->assertRedirect('/login');
    }

    public function test_agency_user_cannot_access(): void
    {
        $agency = User::factory()->agency()->create();
        $listing = Listing::factory()->approved()->create();

        $this->actingAs($agency)->get("/my-listings/{$listing->id}/edit")->assertForbidden();
        $this->actingAs($agency)->put("/my-listings/{$listing->id}", $this->validPayload())->assertForbidden();
    }

    public function test_admin_user_cannot_access(): void
    {
        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->approved()->create();

        $this->actingAs($admin)->get("/my-listings/{$listing->id}/edit")->assertForbidden();
        $this->actingAs($admin)->put("/my-listings/{$listing->id}", $this->validPayload())->assertForbidden();
    }

    public function test_suspended_user_is_logged_out(): void
    {
        $suspended = User::factory()->regularUser()->suspended()->create();
        $listing = Listing::factory()->approved()->for($suspended, 'owner')->create();

        $this->actingAs($suspended)->get("/my-listings/{$listing->id}/edit")->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_editing_a_pending_listing_keeps_it_pending(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->validPayload())
            ->assertRedirect('/profile');

        $listing->refresh();

        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
    }

    public function test_editing_an_approved_listing_reverts_to_pending_and_clears_approved_at(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($user, 'owner')->create();

        $this->assertNotNull($listing->approved_at);

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->validPayload())
            ->assertRedirect('/profile')
            ->assertSessionHas('success', __('app.edit_listing.flash_resubmitted'));

        $listing->refresh();

        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
    }

    public function test_editing_a_hidden_listing_keeps_it_hidden(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->hidden()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->validPayload())
            ->assertRedirect('/profile');

        $listing->refresh();

        $this->assertSame(ListingStatus::Hidden, $listing->status);
    }

    public function test_rejected_listing_cannot_be_edited(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->rejected()->for($user, 'owner')->create();

        $this->actingAs($user)->get("/my-listings/{$listing->id}/edit")->assertForbidden();
        $this->actingAs($user)->put("/my-listings/{$listing->id}", $this->validPayload())->assertForbidden();
    }

    public function test_success_flash_is_plain_update_when_listing_was_not_approved(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->validPayload())
            ->assertSessionHas('success', __('app.edit_listing.flash_updated'));
    }

    public function test_slug_regenerates_when_the_title_changes(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create([
            'title' => 'Old Title',
            'slug' => 'old-title',
        ]);

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->validPayload(['title' => 'Brand New Title']));

        $this->assertSame('brand-new-title', $listing->refresh()->slug);
    }

    public function test_slug_is_preserved_when_the_title_is_unchanged(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create([
            'title' => 'Seaside Villa',
            'slug' => 'seaside-villa-7',
        ]);

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->validPayload(['title' => 'Seaside Villa']));

        $this->assertSame('seaside-villa-7', $listing->refresh()->slug);
    }

    public function test_slug_uniqueness_check_excludes_the_listings_own_row(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create([
            'title' => 'Garden Townhouse',
            'slug' => 'garden-townhouse',
        ]);

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->validPayload(['title' => 'Garden Townhouse']));

        $this->assertSame('garden-townhouse', $listing->refresh()->slug);
    }

    public function test_slug_collision_with_another_listing_appends_suffix(): void
    {
        $user = User::factory()->regularUser()->create();
        Listing::factory()->approved()->for($user, 'owner')->create([
            'title' => 'Mountain Lodge',
            'slug' => 'mountain-lodge',
        ]);
        $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create([
            'title' => 'Different Title',
            'slug' => 'different-title',
        ]);

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->validPayload(['title' => 'Mountain Lodge']));

        $this->assertSame('mountain-lodge-2', $listing->refresh()->slug);
    }

    public function test_server_controlled_fields_cannot_be_set_by_request_payload(): void
    {
        $user = User::factory()->regularUser()->create();
        $other = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create();

        $this->actingAs($user)->put("/my-listings/{$listing->id}", $this->validPayload([
            'agency_id' => 999,
            'status' => ListingStatus::Approved->value,
            'user_id' => $other->id,
        ]));

        $listing->refresh();

        $this->assertSame($user->id, $listing->user_id);
        $this->assertNull($listing->agency_id);
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
    }
}
