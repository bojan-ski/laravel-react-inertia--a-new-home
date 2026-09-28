<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminListingReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function pendingListing(): Listing
    {
        return Listing::factory()
            ->for(User::factory()->regularUser()->state(['name' => 'Jane Doe', 'email' => 'jane@example.test']), 'owner')
            ->pendingReview()
            ->create(['contact_email' => 'contact@example.test']);
    }

    public function test_admin_can_review_a_pending_listing_in_full(): void
    {
        $listing = $this->pendingListing();
        ListingImage::factory()->count(2)->for($listing)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Listings/Show')
                ->where('listing.id', $listing->id)
                ->where('listing.title', $listing->title)
                ->where('listing.description', $listing->description)
                ->where('listing.addressLine', $listing->address_line)
                ->where('listing.contactEmail', 'contact@example.test')
                ->has('listing.images', 2)
                ->where('listing.status', 'pending_review')
                ->where('listing.ownerType', 'user')
                ->where('listing.ownerName', 'Jane Doe')
                ->where('listing.ownerEmail', 'jane@example.test')
                ->where('listing.rejectionReason', null)
                ->where('canModerate', true)
                ->where('translations', __('app.admin.listings'))
                ->where('listingTranslations', __('app.listings.show'))
            );
    }

    public function test_agency_listing_shows_the_agency_as_owner(): void
    {
        $agency = Agency::factory()->approved()->create(['name' => 'Prime Estates', 'email' => 'hello@prime.test']);
        $listing = Listing::factory()->pendingReview()->create(['user_id' => null, 'agency_id' => $agency->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.ownerType', 'agency')
                ->where('listing.ownerName', 'Prime Estates')
                ->where('listing.ownerEmail', 'hello@prime.test')
            );
    }

    public function test_approved_listing_can_be_viewed_but_not_moderated(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.status', 'approved')
                ->where('canModerate', false)
            );
    }

    public function test_rejected_soft_deleted_listing_shows_the_rejection_reason(): void
    {
        $listing = Listing::factory()->rejected()->create(['rejection_reason' => 'Photos do not match.']);
        $listing->delete();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.status', 'rejected')
                ->where('listing.rejectionReason', 'Photos do not match.')
                ->where('canModerate', false)
            );
    }

    public function test_rejecting_from_the_review_page_returns_to_it(): void
    {
        Notification::fake();
        $listing = $this->pendingListing();
        $reviewUrl = "/admin/listings/{$listing->id}";
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from($reviewUrl)
            ->post("{$reviewUrl}/reject", ['reason' => 'Not acceptable.'])
            ->assertRedirect($reviewUrl);

        $this->actingAs($admin)
            ->get($reviewUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.status', 'rejected')
                ->where('listing.rejectionReason', 'Not acceptable.')
                ->where('canModerate', false)
            );
    }

    public function test_approving_from_the_review_page_returns_to_it(): void
    {
        Notification::fake();
        $listing = $this->pendingListing();
        $reviewUrl = "/admin/listings/{$listing->id}";
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from($reviewUrl)
            ->post("{$reviewUrl}/approve")
            ->assertRedirect($reviewUrl);

        $this->actingAs($admin)
            ->get($reviewUrl)
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.status', 'approved')
                ->where('canModerate', false)
            );
    }

    public function test_unknown_listing_returns_404(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/listings/999999')
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $listing = $this->pendingListing();

        $this->get("/admin/listings/{$listing->id}")->assertRedirect('/login');
    }

    public function test_regular_user_gets_403(): void
    {
        $listing = $this->pendingListing();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertForbidden();
    }

    public function test_listing_owner_gets_403(): void
    {
        $listing = $this->pendingListing();

        $this->actingAs($listing->owner)
            ->get("/admin/listings/{$listing->id}")
            ->assertForbidden();
    }

    public function test_agency_user_gets_403(): void
    {
        $listing = $this->pendingListing();

        $this->actingAs(User::factory()->agency()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertForbidden();
    }

    public function test_public_listing_page_still_hides_pending_listings_from_admins(): void
    {
        $listing = $this->pendingListing();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/listings/{$listing->slug}")
            ->assertNotFound();
    }
}
