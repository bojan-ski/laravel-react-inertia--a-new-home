<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\ListingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SimilarListingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function listing(array $overrides = [], string $state = 'approved'): Listing
    {
        return Listing::factory()->{$state}()->create(array_merge([
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => 300000,
            'city' => 'Belgrade',
        ], $overrides));
    }

    /**
     * @return list<int>
     */
    private function similarIds(Listing $listing, ?User $viewer = null): array
    {
        $similar = app(ListingService::class)->getSimilarListings(
            $listing,
            $viewer ?? User::factory()->regularUser()->create(),
        );

        return array_column($similar, 'id');
    }

    public function test_similar_listings_are_a_deferred_prop(): void
    {
        $listing = $this->listing();
        $similar = $this->listing();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Show')
                ->missing('similarListings')
                ->loadDeferredProps(fn (Assert $reload) => $reload
                    ->has('similarListings', 1)
                    ->where('similarListings.0.id', $similar->id)
                )
            );
    }

    public function test_only_the_same_listing_and_property_type_are_included(): void
    {
        $listing = $this->listing();
        $match = $this->listing();
        $this->listing(['listing_type' => 'rent']);
        $this->listing(['property_type' => 'house']);

        $this->assertSame([$match->id], $this->similarIds($listing));
    }

    public function test_the_current_listing_is_never_included(): void
    {
        $listing = $this->listing();

        $this->assertSame([], $this->similarIds($listing));
    }

    public function test_listings_outside_the_thirty_percent_price_band_are_excluded(): void
    {
        $listing = $this->listing(['price' => 300000]);
        $lowInside = $this->listing(['price' => 215000]);
        $highInside = $this->listing(['price' => 385000]);
        $this->listing(['price' => 200000]);
        $this->listing(['price' => 400000]);

        $this->assertEqualsCanonicalizing([$lowInside->id, $highInside->id], $this->similarIds($listing));
    }

    public function test_same_city_ranks_first_then_closest_price(): void
    {
        $listing = $this->listing(['price' => 300000, 'city' => 'Belgrade']);
        $otherCityExact = $this->listing(['price' => 300000, 'city' => 'Novi Sad']);
        $sameCityFar = $this->listing(['price' => 380000, 'city' => 'Belgrade']);
        $sameCityClose = $this->listing(['price' => 310000, 'city' => 'Belgrade']);
        $otherCityFar = $this->listing(['price' => 250000, 'city' => 'Novi Sad']);

        $this->assertSame(
            [$sameCityClose->id, $sameCityFar->id, $otherCityExact->id, $otherCityFar->id],
            $this->similarIds($listing),
        );
    }

    public function test_newest_approval_breaks_a_price_tie(): void
    {
        $listing = $this->listing();
        $older = $this->listing(['price' => 320000, 'approved_at' => now()->subDays(10)]);
        $newer = $this->listing(['price' => 280000, 'approved_at' => now()->subDays(2)]);

        $this->assertSame([$newer->id, $older->id], $this->similarIds($listing));
    }

    public function test_at_most_four_listings_are_returned(): void
    {
        $listing = $this->listing();

        foreach (range(1, 6) as $step) {
            $this->listing(['price' => 300000 + $step * 1000]);
        }

        $this->assertCount(4, $this->similarIds($listing));
    }

    public function test_listings_that_are_not_publicly_visible_never_appear(): void
    {
        $listing = $this->listing();
        $visible = $this->listing(['price' => 350000]);

        $this->listing([], 'pendingReview');
        $this->listing([], 'hidden');
        $this->listing([], 'sold');
        $this->listing([], 'expired');
        $this->listing()->delete();
        $this->listing(['user_id' => User::factory()->regularUser()->suspended()]);
        $this->listing([
            'user_id' => null,
            'agency_id' => Agency::factory()->approved()->create([
                'user_id' => User::factory()->agency()->suspended(),
            ])->id,
        ]);

        $this->assertSame([$visible->id], $this->similarIds($listing));
    }

    public function test_cards_include_bookmark_state_and_bookmark_permission(): void
    {
        $viewer = User::factory()->regularUser()->create();
        $listing = $this->listing();
        $bookmarked = $this->listing(['price' => 301000]);
        $own = $this->listing(['price' => 302000, 'user_id' => $viewer->id]);
        $other = $this->listing(['price' => 303000]);
        Bookmark::factory()->create(['user_id' => $viewer->id, 'listing_id' => $bookmarked->id]);
        $image = ListingImage::factory()->create(['listing_id' => $bookmarked->id]);
        $this->assertNotEmpty($image->small_path);

        $this->actingAs($viewer)
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps(fn (Assert $reload) => $reload
                    ->has('similarListings', 3)
                    ->has('similarListings.0', fn (Assert $card) => $card
                        ->where('id', $bookmarked->id)
                        ->where('title', $bookmarked->title)
                        ->where('slug', $bookmarked->slug)
                        ->where('isBookmarked', true)
                        ->where('canBookmark', true)
                        ->where('imageUrl', fn (?string $url): bool => $url !== null
                            && str_ends_with($url, $image->small_path))
                        ->etc()
                    )
                    ->where('similarListings.1.id', $own->id)
                    ->where('similarListings.1.isBookmarked', false)
                    ->where('similarListings.1.canBookmark', false)
                    ->where('similarListings.2.id', $other->id)
                    ->where('similarListings.2.isBookmarked', false)
                    ->where('similarListings.2.canBookmark', true)
                )
            );
    }

    public function test_the_query_count_does_not_grow_with_the_number_of_cards(): void
    {
        $viewer = User::factory()->regularUser()->create();
        $listing = $this->listing();

        $addCards = function (int $firstPrice): void {
            $userListing = $this->listing(['price' => $firstPrice]);
            $agencyListing = $this->listing([
                'price' => $firstPrice + 1000,
                'user_id' => null,
                'agency_id' => Agency::factory()->approved()->create()->id,
            ]);
            ListingImage::factory()->create(['listing_id' => $userListing->id]);
            ListingImage::factory()->create(['listing_id' => $agencyListing->id]);
        };

        $countQueries = function () use ($listing, $viewer): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            app(ListingService::class)->getSimilarListings($listing, $viewer);

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $addCards(301000);
        $this->assertCount(2, $this->similarIds($listing, $viewer));
        $withTwo = $countQueries();

        $addCards(303000);
        $this->assertCount(4, $this->similarIds($listing, $viewer));
        $this->assertSame($withTwo, $countQueries());
    }

    public function test_no_matches_yields_an_empty_list(): void
    {
        $listing = $this->listing();
        $this->listing(['property_type' => 'villa']);

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps(fn (Assert $reload) => $reload->has('similarListings', 0))
            );
    }

    public function test_the_page_receives_mortgage_defaults_and_the_new_copy(): void
    {
        $listing = $this->listing();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('mortgageDefaults.down_payment_percent', 20)
                ->where('mortgageDefaults.interest_rate', 3.5)
                ->where('mortgageDefaults.term_years', 25)
                ->where('mortgageDefaults.term_options', [10, 15, 20, 25, 30])
                ->where('translations.show.mortgage.heading', 'Mortgage Calculator')
                ->where('translations.show.mortgage.disclaimer', 'Estimate only. Not a loan offer. Actual terms depend on your lender.')
                ->where('translations.show.similar.heading', 'Similar Properties')
                ->where('translations.show.gallery.counter', ':current / :total')
            );
    }

    public function test_the_admin_review_page_receives_the_gallery_copy(): void
    {
        $listing = $this->listing([], 'pendingReview');

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listingTranslations.gallery.dialog_label', 'Photo gallery')
            );
    }
}
