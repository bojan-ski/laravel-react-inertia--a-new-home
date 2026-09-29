<?php

namespace Tests\Feature;

use App\Enums\Amenity;
use App\Enums\ListingStatus;
use App\Models\Agency;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingAmenitiesTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Riverside Apartment',
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => 285000,
            'description' => 'Bright apartment by the river.',
            'country' => 'Austria',
            'city' => 'Vienna',
            'bedrooms' => 2,
            'bathrooms' => 1,
            'area_value' => 84,
        ], $overrides);
    }

    private function approvedAgencyOwner(): User
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id]);

        return $user->refresh();
    }

    /**
     * @return array<int, string>
     */
    private function storedAmenities(Listing $listing): array
    {
        return $listing->refresh()->amenities->map(fn (Amenity $amenity): string => $amenity->value)->all();
    }

    public function test_enum_values_are_stable(): void
    {
        $this->assertSame([
            'parking', 'garage', 'elevator', 'balcony', 'terrace', 'garden', 'pool',
            'air_conditioning', 'central_heating', 'furnished', 'pets_allowed',
            'storage', 'fireplace', 'accessible', 'security',
        ], array_column(Amenity::cases(), 'value'));
    }

    public function test_lang_labels_cover_every_amenity_in_enum_order(): void
    {
        $this->assertSame(
            array_column(Amenity::cases(), 'value'),
            array_keys(__('app.listings.amenities')),
        );
    }

    public function test_amenities_are_fillable(): void
    {
        $this->assertContains('amenities', (new Listing)->getFillable());
    }

    public function test_regular_user_can_create_listing_with_amenities(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->payload(['amenities' => ['parking', 'balcony']]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame(['parking', 'balcony'], $this->storedAmenities(Listing::sole()));
    }

    public function test_agency_can_create_listing_with_amenities(): void
    {
        $user = $this->approvedAgencyOwner();

        $this->actingAs($user)
            ->post('/agency/listings', $this->payload(['amenities' => ['elevator', 'garden']]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/agency');

        $this->assertSame(['elevator', 'garden'], $this->storedAmenities(Listing::sole()));
    }

    public function test_regular_user_can_update_listing_amenities(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->create([
            'user_id' => $user->id,
            'amenities' => [Amenity::Parking],
        ]);

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->payload(['amenities' => ['pool', 'fireplace']]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['pool', 'fireplace'], $this->storedAmenities($listing));
    }

    public function test_agency_can_update_listing_amenities(): void
    {
        $user = $this->approvedAgencyOwner();
        $listing = Listing::factory()->pendingReview()->create([
            'user_id' => null,
            'agency_id' => $user->agency->id,
            'amenities' => [Amenity::Garage],
        ]);

        $this->actingAs($user)
            ->put("/agency/listings/{$listing->id}", $this->payload(['amenities' => ['security']]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['security'], $this->storedAmenities($listing));
    }

    public function test_amenities_are_stored_in_enum_order(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->payload(['amenities' => ['security', 'pool', 'parking']]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['parking', 'pool', 'security'], $this->storedAmenities(Listing::sole()));
    }

    public function test_missing_amenities_store_an_empty_array(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->payload())
            ->assertSessionHasNoErrors();

        $listing = Listing::sole();
        $this->assertSame([], $this->storedAmenities($listing));
        $this->assertSame('[]', $listing->getRawOriginal('amenities'));
    }

    public function test_empty_submission_clears_existing_amenities(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->create([
            'user_id' => $user->id,
            'amenities' => [Amenity::Parking, Amenity::Balcony],
        ]);

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->payload(['amenities' => []]))
            ->assertSessionHasNoErrors();

        $this->assertSame([], $this->storedAmenities($listing));
    }

    public function test_unknown_amenity_is_rejected(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->from('/my-listings/create')
            ->post('/my-listings', $this->payload(['amenities' => ['parking', 'helipad']]))
            ->assertRedirect('/my-listings/create')
            ->assertSessionHasErrors('amenities.1');

        $this->assertSame(0, Listing::count());
    }

    public function test_duplicate_amenity_is_rejected(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->payload(['amenities' => ['parking', 'parking']]))
            ->assertSessionHasErrors(['amenities.0', 'amenities.1']);

        $this->assertSame(0, Listing::count());
    }

    public function test_non_array_amenities_are_rejected(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->payload(['amenities' => 'parking']))
            ->assertSessionHasErrors('amenities');

        $this->assertSame(0, Listing::count());
    }

    public function test_agency_update_rejects_unknown_amenity(): void
    {
        $user = $this->approvedAgencyOwner();
        $listing = Listing::factory()->pendingReview()->create([
            'user_id' => null,
            'agency_id' => $user->agency->id,
            'amenities' => [Amenity::Garage],
        ]);

        $this->actingAs($user)
            ->put("/agency/listings/{$listing->id}", $this->payload(['amenities' => ['helipad']]))
            ->assertSessionHasErrors('amenities.0');

        $this->assertSame(['garage'], $this->storedAmenities($listing));
    }

    public function test_changing_amenities_on_approved_listing_returns_it_to_pending_review(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create(array_merge($this->payload(), [
            'user_id' => $user->id,
            'amenities' => [Amenity::Parking],
        ]));

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->payload(['amenities' => ['parking', 'garden']]))
            ->assertSessionHasNoErrors();

        $listing->refresh();
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
        $this->assertSame(['parking', 'garden'], $this->storedAmenities($listing));
    }

    public function test_show_and_form_payloads_include_amenity_values(): void
    {
        $listing = Listing::factory()->approved()->create([
            'amenities' => [Amenity::Parking, Amenity::AirConditioning],
        ]);

        $this->assertSame(['parking', 'air_conditioning'], $listing->toShowArray()['amenities']);
        $this->assertSame(['parking', 'air_conditioning'], $listing->toFormArray()['amenities']);
        $this->assertArrayNotHasKey('amenities', $listing->toCardArray());
    }

    public function test_payloads_emit_empty_array_for_legacy_null_amenities(): void
    {
        $listing = Listing::factory()->approved()->create(['amenities' => null]);

        $this->assertSame([], $listing->toShowArray()['amenities']);
        $this->assertSame([], $listing->toFormArray()['amenities']);
    }

    public function test_listing_show_page_receives_amenities_and_labels(): void
    {
        $listing = Listing::factory()->approved()->create(['amenities' => [Amenity::Garden]]);

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Show')
                ->where('listing.amenities', ['garden'])
                ->where('translations.amenities.garden', 'Garden')
                ->where('translations.show.amenities_heading', 'Amenities'));
    }

    public function test_regular_user_form_pages_receive_amenity_labels(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->create([
            'user_id' => $user->id,
            'amenities' => [Amenity::Balcony],
        ]);

        $this->actingAs($user)
            ->get('/my-listings/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Create')
                ->where('amenityLabels.pool', 'Swimming pool')
                ->where('translations.sections.amenities', 'Amenities'));

        $this->actingAs($user)
            ->get("/my-listings/{$listing->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Edit')
                ->where('amenityLabels.pool', 'Swimming pool')
                ->where('listing.amenities', ['balcony'])
                ->where('translations.sections.amenities', 'Amenities'));
    }

    public function test_agency_form_pages_receive_amenity_labels(): void
    {
        $user = $this->approvedAgencyOwner();
        $listing = Listing::factory()->pendingReview()->create([
            'user_id' => null,
            'agency_id' => $user->agency->id,
            'amenities' => [Amenity::Terrace],
        ]);

        $this->actingAs($user)
            ->get('/agency/listings/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agency/Listings/Create')
                ->where('amenityLabels.accessible', 'Wheelchair accessible')
                ->where('translations.sections.amenities', 'Amenities'));

        $this->actingAs($user)
            ->get("/agency/listings/{$listing->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agency/Listings/Edit')
                ->where('amenityLabels.accessible', 'Wheelchair accessible')
                ->where('listing.amenities', ['terrace'])
                ->where('translations.sections.amenities', 'Amenities'));
    }

    public function test_admin_review_page_receives_amenities_and_labels(): void
    {
        $listing = Listing::factory()->pendingReview()->create(['amenities' => [Amenity::Furnished]]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Listings/Show')
                ->where('listing.amenities', ['furnished'])
                ->where('amenityLabels.furnished', 'Furnished')
                ->where('listingTranslations.amenities_heading', 'Amenities'));
    }
}
