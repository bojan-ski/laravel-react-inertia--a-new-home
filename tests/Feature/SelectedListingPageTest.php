<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SelectedListingPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_regular_user_can_view_approved_listing(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Show')
                ->where('listing.id', $listing->id)
            );
    }

    public function test_agency_user_can_view_approved_listing(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->agency()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Listings/Show'));
    }

    public function test_admin_can_view_approved_listing(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Listings/Show'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->get("/listings/{$listing->slug}")->assertRedirect('/login');
    }

    public function test_after_login_user_is_redirected_to_originally_requested_listing(): void
    {
        $listing = Listing::factory()->approved()->create();
        $user = User::factory()->regularUser()->create(['password' => 'password']);

        $this->get("/listings/{$listing->slug}")->assertRedirect('/login');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect("/listings/{$listing->slug}");
    }

    public function test_nonexistent_slug_returns_404(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/listings/this-slug-does-not-exist')
            ->assertNotFound();
    }

    public function test_pending_listing_returns_404(): void
    {
        $listing = Listing::factory()->pendingReview()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertNotFound();
    }

    public function test_rejected_listing_returns_404(): void
    {
        $listing = Listing::factory()->rejected()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertNotFound();
    }

    public function test_hidden_listing_returns_404(): void
    {
        $listing = Listing::factory()->hidden()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertNotFound();
    }

    public function test_soft_deleted_listing_returns_404(): void
    {
        $listing = Listing::factory()->approved()->create();
        $listing->delete();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertNotFound();
    }

    public function test_listing_with_suspended_owner_returns_404(): void
    {
        $listing = Listing::factory()->approved()->for(User::factory()->suspended(), 'owner')->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertNotFound();
    }

    public function test_payload_includes_listing_details_and_contact_fields(): void
    {
        $listing = Listing::factory()->approved()->create([
            'title' => 'Sunlit Garden Villa',
            'price' => 425000,
            'currency' => 'EUR',
            'city' => 'Belgrade',
            'country' => 'Serbia',
            'description' => 'A bright and spacious family home.',
            'contact_name' => 'Jane Doe',
            'contact_email' => 'jane@example.test',
            'contact_phone' => '+381 11 234 567',
        ]);

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.title', 'Sunlit Garden Villa')
                ->where('listing.price', '425000.00')
                ->where('listing.city', 'Belgrade')
                ->where('listing.country', 'Serbia')
                ->where('listing.description', 'A bright and spacious family home.')
                ->where('listing.contactName', 'Jane Doe')
                ->where('listing.contactEmail', 'jane@example.test')
                ->where('listing.contactPhone', '+381 11 234 567')
            );
    }

    public function test_is_bookmarked_is_false_until_spec_eleven(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('isBookmarked', false)
                ->where('canStartChat', true)
            );
    }
}
