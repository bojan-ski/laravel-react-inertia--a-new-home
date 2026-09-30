<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\ListingImage;
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

    public function test_agency_listing_shows_the_agency_contact_and_a_link_to_the_agency(): void
    {
        $agency = Agency::factory()->approved()->create([
            'name' => 'Adriatic Homes',
            'email' => 'hello@adriatic.test',
            'phone' => '+385 1 555 0101',
        ]);
        $listing = Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.contactName', 'Adriatic Homes')
                ->where('listing.contactEmail', 'hello@adriatic.test')
                ->where('listing.contactPhone', '+385 1 555 0101')
                ->where('listing.agency', ['name' => 'Adriatic Homes', 'slug' => $agency->slug])
            );
    }

    public function test_agency_link_is_null_when_the_agency_is_not_publicly_visible(): void
    {
        $agency = Agency::factory()->pendingVerification()->create(['name' => 'Pending Realty']);
        $listing = Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.contactName', 'Pending Realty')
                ->where('listing.agency', null)
            );
    }

    public function test_user_listing_contact_override_wins_over_the_account_details(): void
    {
        $owner = User::factory()->regularUser()->create(['name' => 'Account Name', 'email' => 'account@example.test']);
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create([
            'contact_name' => 'Jane Doe',
            'contact_email' => 'jane@example.test',
            'contact_phone' => '+381 11 234 567',
        ]);

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.contactName', 'Jane Doe')
                ->where('listing.contactEmail', 'jane@example.test')
                ->where('listing.contactPhone', '+381 11 234 567')
                ->where('listing.agency', null)
            );
    }

    public function test_user_listing_without_override_falls_back_to_the_owner_account(): void
    {
        $owner = User::factory()->regularUser()->create(['name' => 'Account Name', 'email' => 'account@example.test']);
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.contactName', 'Account Name')
                ->where('listing.contactEmail', 'account@example.test')
                ->where('listing.contactPhone', null)
                ->where('listing.agency', null)
            );
    }

    public function test_edit_form_keeps_the_raw_contact_override_fields(): void
    {
        $owner = User::factory()->regularUser()->create(['name' => 'Account Name']);
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $form = $listing->load('owner')->toFormArray();

        $this->assertNull($form['contactName']);
        $this->assertNull($form['contactEmail']);
    }

    public function test_payload_has_an_iso_approval_date_image_thumbnails_and_full_translations(): void
    {
        $listing = Listing::factory()->approved()->create(['approved_at' => '2026-09-12 10:00:00']);
        $image = ListingImage::factory()->for($listing)->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.approvedAt', fn (string $date): bool => str_starts_with($date, '2026-09-12T10:00:00'))
                ->where('listing.images.0.thumbUrl', fn (string $url): bool => str_ends_with($url, $image->small_path))
                ->where('listing.images.0.url', fn (string $url): bool => str_ends_with($url, $image->large_path))
                ->where('translations', __('app.listings'))
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
