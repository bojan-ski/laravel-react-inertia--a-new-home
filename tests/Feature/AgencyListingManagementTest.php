<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\UserStatus;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Models\Agency;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\ListingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencyListingManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array{0: User, 1: Agency}
     */
    private function agencyOwner(string $agencyState = 'approved'): array
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->{$agencyState}()->create(['user_id' => $user->id]);

        return [$user, $agency];
    }

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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Agency Listing Title',
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => 250000,
            'currency' => 'EUR',
            'description' => 'A detailed description of the property.',
            'country' => 'Serbia',
            'city' => 'Belgrade',
            'address_line' => '12 Main Street',
            'postal_code' => '11000',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area_value' => 120,
            'area_unit' => 'm²',
            'year_built' => 2015,
        ], $overrides);
    }

    public function test_approved_agency_can_view_create_page(): void
    {
        [$user] = $this->agencyOwner('approved');

        $this->actingAs($user)
            ->get('/agency/listings/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Agency/Listings/Create'));
    }

    public function test_pending_agency_cannot_view_create_page(): void
    {
        [$user] = $this->agencyOwner('pendingVerification');

        $this->actingAs($user)->get('/agency/listings/create')->assertForbidden();
    }

    public function test_rejected_agency_cannot_view_create_page(): void
    {
        [$user] = $this->agencyOwner('rejected');

        $this->actingAs($user)->get('/agency/listings/create')->assertForbidden();
    }

    public function test_hidden_agency_cannot_view_create_page(): void
    {
        [$user] = $this->agencyOwner('hidden');

        $this->actingAs($user)->get('/agency/listings/create')->assertForbidden();
    }

    public function test_agency_user_without_agency_is_redirected_to_setup(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)->get('/agency/listings/create')->assertRedirect('/agency/setup');
    }

    public function test_agency_user_without_agency_is_redirected_to_setup_on_store(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->post('/agency/listings', $this->validPayload())
            ->assertRedirect('/agency/setup');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/agency/listings/create')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_agency_listing_routes(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->get('/agency/listings/create')->assertForbidden();
        $this->actingAs($user)->post('/agency/listings', $this->validPayload())->assertForbidden();
    }

    public function test_admin_cannot_access_agency_listing_routes(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get('/agency/listings/create')->assertForbidden();
        $this->actingAs($user)->post('/agency/listings', $this->validPayload())->assertForbidden();
    }

    public function test_suspended_agency_user_is_logged_out(): void
    {
        [$user] = $this->agencyOwner('approved');
        $user->status = UserStatus::Suspended;
        $user->suspended_at = now();
        $user->save();

        $this->actingAs($user)->get('/agency/listings/create')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_approved_agency_can_create_listing(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');

        $this->actingAs($user)
            ->post('/agency/listings', $this->validPayload(['title' => 'Brand New Listing']))
            ->assertRedirect('/agency')
            ->assertSessionHas('success');

        $listing = Listing::where('title', 'Brand New Listing')->firstOrFail();

        $this->assertSame($agency->id, $listing->agency_id);
        $this->assertNull($listing->user_id);
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
        $this->assertNull($listing->rejected_at);
        $this->assertNull($listing->rejection_reason);
        $this->assertSame('brand-new-listing', $listing->slug);
    }

    public function test_created_listing_strips_contact_fields_even_when_submitted(): void
    {
        [$user] = $this->agencyOwner('approved');

        $this->actingAs($user)->post('/agency/listings', $this->validPayload([
            'title' => 'Contact Strip Test',
            'contact_name' => 'Sneaky Name',
            'contact_email' => 'sneaky@example.test',
            'contact_phone' => '+1 555 0000',
        ]))->assertRedirect('/agency');

        $listing = Listing::where('title', 'Contact Strip Test')->firstOrFail();

        $this->assertNull($listing->contact_name);
        $this->assertNull($listing->contact_email);
        $this->assertNull($listing->contact_phone);
    }

    public function test_create_generates_unique_slug_on_collision(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $this->agencyListing($agency, 'approved', ['title' => 'Duplicate Title', 'slug' => 'duplicate-title']);

        $this->actingAs($user)->post('/agency/listings', $this->validPayload(['title' => 'Duplicate Title']))
            ->assertRedirect('/agency');

        $this->assertDatabaseHas('listings', ['slug' => 'duplicate-title-2']);
    }

    public function test_pending_agency_cannot_create_listing(): void
    {
        [$user] = $this->agencyOwner('pendingVerification');

        $this->actingAs($user)->post('/agency/listings', $this->validPayload())->assertForbidden();
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_create_validates_required_fields(): void
    {
        [$user] = $this->agencyOwner('approved');

        $this->actingAs($user)->post('/agency/listings', [])
            ->assertSessionHasErrors(['title', 'listing_type', 'property_type', 'price', 'description', 'country', 'city']);
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_approved_agency_can_view_edit_page_for_own_listing(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'pendingReview');

        $this->actingAs($user)
            ->get("/agency/listings/{$listing->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Agency/Listings/Edit')->has('listing'));
    }

    public function test_editing_pending_listing_keeps_it_pending(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'pendingReview');

        $this->actingAs($user)
            ->put("/agency/listings/{$listing->id}", $this->validPayload(['title' => 'Updated Pending']))
            ->assertRedirect('/agency');

        $listing->refresh();
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertSame('Updated Pending', $listing->title);
    }

    public function test_editing_approved_listing_reverts_to_pending(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'approved');

        $this->actingAs($user)
            ->put("/agency/listings/{$listing->id}", $this->validPayload(['title' => 'Updated Approved']))
            ->assertRedirect('/agency')
            ->assertSessionHas('success', __('app.edit_agency_listing.flash_resubmitted'));

        $listing->refresh();
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
    }

    public function test_editing_hidden_listing_keeps_it_hidden(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'hidden');

        $this->actingAs($user)
            ->put("/agency/listings/{$listing->id}", $this->validPayload(['title' => 'Updated Hidden']))
            ->assertRedirect('/agency')
            ->assertSessionHas('success', __('app.edit_agency_listing.flash_updated'));

        $listing->refresh();
        $this->assertSame(ListingStatus::Hidden, $listing->status);
    }

    public function test_editing_rejected_listing_is_forbidden(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'rejected');

        $this->actingAs($user)->get("/agency/listings/{$listing->id}/edit")->assertForbidden();
        $this->actingAs($user)->put("/agency/listings/{$listing->id}", $this->validPayload())->assertForbidden();
    }

    public function test_agency_cannot_edit_another_agencys_listing(): void
    {
        [$user] = $this->agencyOwner('approved');
        [, $otherAgency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($otherAgency, 'pendingReview');

        $this->actingAs($user)->get("/agency/listings/{$listing->id}/edit")->assertForbidden();
        $this->actingAs($user)->put("/agency/listings/{$listing->id}", $this->validPayload())->assertForbidden();
    }

    public function test_agency_cannot_edit_regular_user_listing(): void
    {
        [$user] = $this->agencyOwner('approved');
        $listing = Listing::factory()->pendingReview()->create();

        $this->actingAs($user)->get("/agency/listings/{$listing->id}/edit")->assertForbidden();
        $this->actingAs($user)->put("/agency/listings/{$listing->id}", $this->validPayload())->assertForbidden();
    }

    public function test_edit_regenerates_slug_on_title_change(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'pendingReview', ['title' => 'Original Title', 'slug' => 'original-title']);

        $this->actingAs($user)->put("/agency/listings/{$listing->id}", $this->validPayload(['title' => 'Completely New Title']))
            ->assertRedirect('/agency');

        $this->assertSame('completely-new-title', $listing->refresh()->slug);
    }

    public function test_edit_preserves_slug_when_title_unchanged(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'pendingReview', ['title' => 'Stable Title', 'slug' => 'stable-title-7']);

        $this->actingAs($user)->put("/agency/listings/{$listing->id}", $this->validPayload(['title' => 'Stable Title']))
            ->assertRedirect('/agency');

        $this->assertSame('stable-title-7', $listing->refresh()->slug);
    }

    public function test_edit_strips_contact_fields_and_keeps_agency_ownership(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'pendingReview');

        $this->actingAs($user)->put("/agency/listings/{$listing->id}", $this->validPayload([
            'contact_name' => 'Sneaky',
            'contact_email' => 'sneaky@example.test',
            'contact_phone' => '123',
        ]))->assertRedirect('/agency');

        $listing->refresh();
        $this->assertNull($listing->contact_name);
        $this->assertNull($listing->contact_email);
        $this->assertNull($listing->contact_phone);
        $this->assertNull($listing->user_id);
        $this->assertSame($agency->id, $listing->agency_id);
    }

    public function test_update_error_flash_when_service_throws(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'pendingReview');

        $this->mock(ListingService::class, function ($mock) {
            $mock->shouldReceive('updateForAgency')
                ->once()
                ->andThrow(new InvalidListingStatusTransitionException('boom'));
        });

        $this->actingAs($user)
            ->put("/agency/listings/{$listing->id}", $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('error', __('app.edit_agency_listing.blocked'));
    }

    public function test_approved_agency_can_delete_own_listing_in_any_status(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');

        foreach (['pendingReview', 'approved', 'hidden', 'rejected'] as $state) {
            $listing = $this->agencyListing($agency, $state);

            $this->actingAs($user)
                ->delete("/agency/listings/{$listing->id}")
                ->assertRedirect('/agency')
                ->assertSessionHas('success');

            $this->assertSoftDeleted('listings', ['id' => $listing->id]);
        }
    }

    public function test_deleted_listing_disappears_from_dashboard(): void
    {
        [$user, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'approved');

        $this->actingAs($user)->delete("/agency/listings/{$listing->id}")->assertRedirect('/agency');

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page->has('listings', 0));
    }

    public function test_agency_cannot_delete_another_agencys_listing(): void
    {
        [$user] = $this->agencyOwner('approved');
        [, $otherAgency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($otherAgency, 'approved');

        $this->actingAs($user)->delete("/agency/listings/{$listing->id}")->assertForbidden();
        $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
    }

    public function test_create_for_agency_service_forces_ownership_and_defaults(): void
    {
        [, $agency] = $this->agencyOwner('approved');

        $listing = app(ListingService::class)->createForAgency($agency, $this->validPayload([
            'title' => 'Service Created',
            'contact_name' => 'X',
            'contact_email' => 'x@x.test',
            'contact_phone' => '1',
        ]));

        $this->assertSame($agency->id, $listing->agency_id);
        $this->assertNull($listing->user_id);
        $this->assertNull($listing->contact_name);
        $this->assertNull($listing->contact_email);
        $this->assertNull($listing->contact_phone);
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
    }

    public function test_update_for_agency_service_throws_for_rejected_listing(): void
    {
        [, $agency] = $this->agencyOwner('approved');
        $listing = $this->agencyListing($agency, 'rejected');

        $this->expectException(InvalidListingStatusTransitionException::class);

        app(ListingService::class)->updateForAgency($listing, $this->validPayload());
    }

    public function test_dashboard_exposes_agency_status_for_button_gating(): void
    {
        [$user] = $this->agencyOwner('pendingVerification');

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page->where('agency.status', 'pending_verification'));
    }

    public function test_the_agency_dashboard_query_count_does_not_grow_with_listings(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        $countQueries = function () use ($user): int {
            $actor = $user->fresh();
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($actor)->get('/agency')->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $seed = function () use ($agency): void {
            foreach (['approved', 'sold', 'pendingReview', 'expired'] as $state) {
                $listing = Listing::factory()->{$state}()->create(['user_id' => null, 'agency_id' => $agency->id]);
                ListingImage::factory()->for($listing)->create();
            }
        };

        $seed();
        $seed();
        $withTwo = $countQueries();

        foreach (range(1, 2) as $index) {
            $seed();
        }

        $this->assertSame(16, $agency->listings()->count());
        $this->assertSame($withTwo, $countQueries());
    }
}
