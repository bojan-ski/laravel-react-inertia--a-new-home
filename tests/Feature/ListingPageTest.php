<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_view_listings_page(): void
    {
        $this->get('/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Listings/Index'));
    }

    public function test_authenticated_user_can_view_listings_page(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Listings/Index'));
    }

    public function test_listings_page_passes_the_listings_translations(): void
    {
        $this->get('/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('translations', __('app.listings'))
                ->where('translations.filters.sorts_short', fn ($sortsShort) => array_keys($sortsShort->all()) === array_keys(__('app.listings.filters.sorts')))
            );
    }

    public function test_only_approved_listings_appear(): void
    {
        $visible = Listing::factory()->approved()->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $visible->id)
            );
    }

    public function test_pending_listings_do_not_appear(): void
    {
        $visible = Listing::factory()->approved()->create();
        Listing::factory()->pendingReview()->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $visible->id)
            );
    }

    public function test_rejected_listings_do_not_appear(): void
    {
        $visible = Listing::factory()->approved()->create();
        Listing::factory()->rejected()->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $visible->id)
            );
    }

    public function test_hidden_listings_do_not_appear(): void
    {
        $visible = Listing::factory()->approved()->create();
        Listing::factory()->hidden()->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $visible->id)
            );
    }

    public function test_soft_deleted_listings_do_not_appear(): void
    {
        $visible = Listing::factory()->approved()->create();
        Listing::factory()->approved()->create()->delete();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $visible->id)
            );
    }

    public function test_listings_with_suspended_owner_do_not_appear(): void
    {
        $visible = Listing::factory()->approved()->create();
        Listing::factory()->approved()->for(User::factory()->suspended(), 'owner')->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $visible->id)
            );
    }

    public function test_results_paginate_at_twelve_per_page(): void
    {
        Listing::factory()->count(15)->approved()->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 12)
                ->where('listings.total', 15)
                ->where('listings.per_page', 12)
                ->where('listings.last_page', 2)
            );

        $this->get('/listings?page=2')
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 3));
    }

    public function test_default_sort_is_newest_first(): void
    {
        $older = Listing::factory()->approved()->create(['approved_at' => now()->subDays(10)]);
        $newer = Listing::factory()->approved()->create(['approved_at' => now()->subDay()]);

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $newer->id)
                ->where('listings.data.1.id', $older->id)
            );
    }

    public function test_oldest_sort_orders_by_approved_at_ascending(): void
    {
        $older = Listing::factory()->approved()->create(['approved_at' => now()->subDays(10)]);
        $newer = Listing::factory()->approved()->create(['approved_at' => now()->subDay()]);

        $this->get('/listings?sort=oldest')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $older->id)
                ->where('listings.data.1.id', $newer->id)
            );
    }

    public function test_price_low_high_sort_orders_by_price_ascending(): void
    {
        $cheap = Listing::factory()->approved()->create(['price' => 100000]);
        $expensive = Listing::factory()->approved()->create(['price' => 800000]);

        $this->get('/listings?sort=price_low_high')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $cheap->id)
                ->where('listings.data.1.id', $expensive->id)
            );
    }

    public function test_price_high_low_sort_orders_by_price_descending(): void
    {
        $cheap = Listing::factory()->approved()->create(['price' => 100000]);
        $expensive = Listing::factory()->approved()->create(['price' => 800000]);

        $this->get('/listings?sort=price_high_low')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $expensive->id)
                ->where('listings.data.1.id', $cheap->id)
            );
    }

    public function test_search_filters_by_title(): void
    {
        $match = Listing::factory()->approved()->create(['title' => 'Zorptastic Garden Villa']);
        Listing::factory()->approved()->create(['title' => 'Plain City Flat']);

        $this->get('/listings?search=Zorptastic')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_search_filters_by_city(): void
    {
        $match = Listing::factory()->approved()->create(['city' => 'Zorptown']);
        Listing::factory()->approved()->create(['city' => 'Belgrade']);

        $this->get('/listings?search=Zorptown')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_search_filters_by_description(): void
    {
        $match = Listing::factory()->approved()->create(['description' => 'A home with a zorptastic rooftop terrace.']);
        Listing::factory()->approved()->create(['description' => 'A regular home with no special features.']);

        $this->get('/listings?search=zorptastic')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_search_is_case_insensitive(): void
    {
        $match = Listing::factory()->approved()->create(['title' => 'Zorptastic Garden Villa']);

        $this->get('/listings?search=zorptastic')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_listing_type_filter(): void
    {
        $sale = Listing::factory()->approved()->create(['listing_type' => 'sale']);
        Listing::factory()->approved()->create(['listing_type' => 'rent']);

        $this->get('/listings?listingType=sale')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $sale->id)
            );
    }

    public function test_property_type_filter(): void
    {
        $house = Listing::factory()->approved()->create(['property_type' => 'house']);
        Listing::factory()->approved()->create(['property_type' => 'land']);

        $this->get('/listings?propertyType=house')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $house->id)
            );
    }

    public function test_city_filter(): void
    {
        $match = Listing::factory()->approved()->create(['city' => 'Belgrade']);
        Listing::factory()->approved()->create(['city' => 'Vienna']);

        $this->get('/listings?city=Belgr')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_min_price_filter(): void
    {
        $expensive = Listing::factory()->approved()->create(['price' => 500000]);
        Listing::factory()->approved()->create(['price' => 100000]);

        $this->get('/listings?minPrice=300000')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $expensive->id)
            );
    }

    public function test_max_price_filter(): void
    {
        $cheap = Listing::factory()->approved()->create(['price' => 100000]);
        Listing::factory()->approved()->create(['price' => 500000]);

        $this->get('/listings?maxPrice=300000')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $cheap->id)
            );
    }

    public function test_combined_filters_apply_together(): void
    {
        $match = Listing::factory()->approved()->create([
            'listing_type' => 'sale',
            'property_type' => 'villa',
        ]);
        Listing::factory()->approved()->create([
            'listing_type' => 'rent',
            'property_type' => 'villa',
        ]);
        Listing::factory()->approved()->create([
            'listing_type' => 'sale',
            'property_type' => 'land',
        ]);

        $this->get('/listings?listingType=sale&propertyType=villa')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_empty_state_when_no_results_match(): void
    {
        Listing::factory()->approved()->create(['title' => 'Plain City Flat']);

        $this->get('/listings?search=nonexistentqueryxyz')
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));
    }

    public function test_filters_prop_reflects_active_state(): void
    {
        $this->get('/listings?search=garden&listingType=sale&sort=oldest')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'garden')
                ->where('filters.listingType', 'sale')
                ->where('filters.sort', 'oldest')
            );
    }

    public function test_invalid_sort_fails_validation(): void
    {
        $this->get('/listings?sort=random')->assertSessionHasErrors('sort');
    }

    public function test_invalid_property_type_fails_validation(): void
    {
        $this->get('/listings?propertyType=mansion')->assertSessionHasErrors('propertyType');
    }

    public function test_invalid_min_price_fails_validation(): void
    {
        $this->get('/listings?minPrice=abc')->assertSessionHasErrors('minPrice');
    }

    public function test_the_listings_query_count_does_not_grow_with_listings(): void
    {
        $viewer = User::factory()->regularUser()->create();

        $countQueries = function () use ($viewer): int {
            $actor = $viewer->fresh();
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($actor)->get('/listings')->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $seed = function () use ($viewer): void {
            $listings = [
                Listing::factory()->approved()->create(),
                Listing::factory()->approved()->create(['user_id' => null, 'agency_id' => Agency::factory()->approved()->create()->id]),
            ];

            foreach ($listings as $listing) {
                ListingImage::factory()->for($listing)->create();
                Bookmark::factory()->create(['user_id' => $viewer->id, 'listing_id' => $listing->id]);
            }
        };

        $seed();
        $seed();
        $withTwo = $countQueries();

        foreach (range(1, 2) as $index) {
            $seed();
        }

        $this->assertSame(8, Listing::count());
        $this->assertSame($withTwo, $countQueries());
    }
}
