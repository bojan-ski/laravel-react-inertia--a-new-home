<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencyPublicPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function agencyListing(Agency $agency, string $state = 'approved', array $overrides = []): Listing
    {
        return Listing::factory()->{$state}()->create(array_merge([
            'user_id' => null,
            'agency_id' => $agency->id,
        ], $overrides));
    }

    public function test_guest_can_view_an_approved_agency(): void
    {
        $agency = Agency::factory()->approved()->create(['name' => 'Harbor Homes']);

        $this->get("/agencies/{$agency->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agencies/Show')
                ->where('agency.id', $agency->id)
                ->where('agency.name', 'Harbor Homes')
                ->where('agency.email', $agency->email)
                ->where('translations.show.back', __('app.agencies.show.back'))
                ->has('listingTranslations.card'));
    }

    public function test_signed_in_user_can_view_an_approved_agency(): void
    {
        $agency = Agency::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/agencies/{$agency->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Agencies/Show'));
    }

    public function test_only_this_agencys_publicly_visible_listings_are_shown(): void
    {
        $agency = Agency::factory()->approved()->create();
        $visible = $this->agencyListing($agency);
        $this->agencyListing($agency, 'pendingReview');
        $this->agencyListing($agency, 'hidden');
        $this->agencyListing($agency)->delete();
        $this->agencyListing(Agency::factory()->approved()->create());
        Listing::factory()->approved()->create();

        $this->get("/agencies/{$agency->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.total', 1)
                ->where('listings.data.0.id', $visible->id));
    }

    public function test_listings_are_newest_first_and_paginated_by_twelve(): void
    {
        $agency = Agency::factory()->approved()->create();

        foreach (range(1, 13) as $daysAgo) {
            $this->agencyListing($agency, 'approved', ['approved_at' => now()->subDays($daysAgo)]);
        }

        $newest = Listing::where('agency_id', $agency->id)->orderByDesc('approved_at')->first();

        $this->get("/agencies/{$agency->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 12)
                ->where('listings.total', 13)
                ->where('listings.data.0.id', $newest->id));

        $this->get("/agencies/{$agency->slug}?page=2")
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 1));
    }

    public function test_listing_cards_carry_bookmark_state(): void
    {
        $agency = Agency::factory()->approved()->create();
        $listing = $this->agencyListing($agency);
        $user = User::factory()->regularUser()->create();
        Bookmark::factory()->create(['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->actingAs($user)
            ->get("/agencies/{$agency->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.isBookmarked', true)
                ->where('listings.data.0.canBookmark', true));
    }

    public function test_agency_owner_cannot_bookmark_own_listings_on_the_page(): void
    {
        $agency = Agency::factory()->approved()->create();
        $this->agencyListing($agency);

        $this->actingAs($agency->user)
            ->get("/agencies/{$agency->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('listings.data.0.canBookmark', false));
    }

    public function test_agency_owner_bookmark_request_is_forbidden(): void
    {
        $agency = Agency::factory()->approved()->create();
        $listing = $this->agencyListing($agency);

        $this->actingAs($agency->user)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertForbidden();

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_pending_agency_returns_404(): void
    {
        $agency = Agency::factory()->pendingVerification()->create();

        $this->get("/agencies/{$agency->slug}")->assertNotFound();
    }

    public function test_hidden_agency_returns_404(): void
    {
        $agency = Agency::factory()->hidden()->create();

        $this->get("/agencies/{$agency->slug}")->assertNotFound();
    }

    public function test_agency_with_suspended_owner_returns_404(): void
    {
        $agency = Agency::factory()->approved()->create();
        $agency->user->forceFill(['status' => 'suspended', 'suspended_at' => now()])->save();

        $this->get("/agencies/{$agency->slug}")->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/agencies/does-not-exist')->assertNotFound();
    }

    public function test_directory_cards_expose_the_slug_and_view_label(): void
    {
        $agency = Agency::factory()->approved()->create();

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agencies.data.0.slug', $agency->slug)
                ->where('translations.view_agency', __('app.agencies.view_agency')));
    }
}
