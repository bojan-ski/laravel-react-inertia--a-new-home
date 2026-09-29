<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Services\ListingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingExtraFiltersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_min_bedrooms_filter_excludes_smaller_and_null(): void
    {
        $match = Listing::factory()->approved()->create(['bedrooms' => 3]);
        Listing::factory()->approved()->create(['bedrooms' => 1]);
        Listing::factory()->approved()->create(['bedrooms' => null]);

        $this->get('/listings?minBedrooms=2')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_min_bedrooms_filter_includes_exact_value(): void
    {
        $match = Listing::factory()->approved()->create(['bedrooms' => 2]);

        $this->get('/listings?minBedrooms=2')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_min_bathrooms_filter_excludes_smaller_and_null(): void
    {
        $match = Listing::factory()->approved()->create(['bathrooms' => 2]);
        Listing::factory()->approved()->create(['bathrooms' => 1]);
        Listing::factory()->approved()->create(['bathrooms' => null]);

        $this->get('/listings?minBathrooms=2')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_min_area_filter_excludes_smaller_and_null(): void
    {
        $match = Listing::factory()->approved()->create(['area_value' => 120]);
        Listing::factory()->approved()->create(['area_value' => 60]);
        Listing::factory()->approved()->create(['area_value' => null]);

        $this->get('/listings?minArea=100')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_max_area_filter_excludes_larger_and_null(): void
    {
        $match = Listing::factory()->approved()->create(['area_value' => 60]);
        Listing::factory()->approved()->create(['area_value' => 120]);
        Listing::factory()->approved()->create(['area_value' => null]);

        $this->get('/listings?maxArea=100')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_area_range_filter(): void
    {
        $match = Listing::factory()->approved()->create(['area_value' => 80]);
        Listing::factory()->approved()->create(['area_value' => 40]);
        Listing::factory()->approved()->create(['area_value' => 150]);

        $this->get('/listings?minArea=50&maxArea=100')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_single_amenity_filter_excludes_listings_without_it(): void
    {
        $match = Listing::factory()->approved()->create(['amenities' => ['pool', 'garden']]);
        Listing::factory()->approved()->create(['amenities' => ['garden']]);
        Listing::factory()->approved()->create(['amenities' => null]);

        $this->get('/listings?amenities[]=pool')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_amenities_filter_requires_all_selected_amenities(): void
    {
        $match = Listing::factory()->approved()->create(['amenities' => ['parking', 'pool', 'garden']]);
        Listing::factory()->approved()->create(['amenities' => ['pool']]);
        Listing::factory()->approved()->create(['amenities' => ['garden']]);
        Listing::factory()->approved()->create(['amenities' => []]);

        $this->get('/listings?amenities[]=pool&amenities[]=garden')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_new_filters_combine_with_existing_ones(): void
    {
        $attributes = [
            'listing_type' => 'sale',
            'price' => 250000,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area_value' => 110,
            'amenities' => ['balcony', 'elevator'],
        ];

        $match = Listing::factory()->approved()->create($attributes);
        Listing::factory()->approved()->create([...$attributes, 'listing_type' => 'rent']);
        Listing::factory()->approved()->create([...$attributes, 'bedrooms' => 1]);
        Listing::factory()->approved()->create([...$attributes, 'bathrooms' => 1]);
        Listing::factory()->approved()->create([...$attributes, 'area_value' => 300]);
        Listing::factory()->approved()->create([...$attributes, 'amenities' => ['balcony']]);
        Listing::factory()->approved()->create([...$attributes, 'price' => 900000]);

        $query = http_build_query([
            'listingType' => 'sale',
            'maxPrice' => 300000,
            'minBedrooms' => 2,
            'minBathrooms' => 2,
            'minArea' => 90,
            'maxArea' => 150,
            'amenities' => ['elevator', 'balcony'],
        ]);

        $this->get("/listings?{$query}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id)
            );
    }

    public function test_max_area_below_min_area_fails_validation(): void
    {
        $this->get('/listings?minArea=100&maxArea=50')->assertSessionHasErrors('maxArea');
    }

    public function test_max_price_below_min_price_fails_validation(): void
    {
        $this->get('/listings?minPrice=300000&maxPrice=100000')->assertSessionHasErrors('maxPrice');
    }

    public function test_max_without_min_passes_validation(): void
    {
        $this->get('/listings?maxPrice=300000&maxArea=100')
            ->assertOk()
            ->assertSessionHasNoErrors();
    }

    public function test_equal_min_and_max_pass_validation(): void
    {
        $this->get('/listings?minPrice=200000&maxPrice=200000&minArea=80&maxArea=80')
            ->assertOk()
            ->assertSessionHasNoErrors();
    }

    public function test_unknown_amenity_fails_validation(): void
    {
        $this->get('/listings?amenities[]=helipad')->assertSessionHasErrors('amenities.0');
    }

    public function test_duplicate_amenity_fails_validation(): void
    {
        $this->get('/listings?amenities[]=pool&amenities[]=pool')->assertSessionHasErrors('amenities.0');
    }

    public function test_non_array_amenities_fails_validation(): void
    {
        $this->get('/listings?amenities=pool')->assertSessionHasErrors('amenities');
    }

    public function test_bedroom_and_bathroom_bounds_fail_validation(): void
    {
        $this->get('/listings?minBedrooms=0')->assertSessionHasErrors('minBedrooms');
        $this->get('/listings?minBedrooms=11')->assertSessionHasErrors('minBedrooms');
        $this->get('/listings?minBedrooms=2.5')->assertSessionHasErrors('minBedrooms');
        $this->get('/listings?minBathrooms=0')->assertSessionHasErrors('minBathrooms');
        $this->get('/listings?minBathrooms=11')->assertSessionHasErrors('minBathrooms');
    }

    public function test_negative_area_fails_validation(): void
    {
        $this->get('/listings?minArea=-1')->assertSessionHasErrors('minArea');
        $this->get('/listings?maxArea=abc')->assertSessionHasErrors('maxArea');
    }

    public function test_filters_prop_has_the_documented_shape_when_empty(): void
    {
        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters', [
                    'search' => '',
                    'listingType' => '',
                    'propertyType' => '',
                    'city' => '',
                    'minPrice' => '',
                    'maxPrice' => '',
                    'minBedrooms' => '',
                    'minBathrooms' => '',
                    'minArea' => '',
                    'maxArea' => '',
                    'amenities' => [],
                    'sort' => 'newest',
                ])
            );
    }

    public function test_filters_prop_reflects_new_filters_and_orders_amenities_by_enum(): void
    {
        $this->get('/listings?minBedrooms=3&minBathrooms=2&minArea=+50+&maxArea=120&amenities[]=pool&amenities[]=parking')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.minBedrooms', '3')
                ->where('filters.minBathrooms', '2')
                ->where('filters.minArea', '50')
                ->where('filters.maxArea', '120')
                ->where('filters.amenities', ['parking', 'pool'])
            );
    }

    public function test_city_filter_treats_like_wildcards_literally(): void
    {
        $literal = Listing::factory()->approved()->create(['city' => 'Test_City']);
        Listing::factory()->approved()->create(['city' => 'TestXCity']);
        Listing::factory()->approved()->create(['city' => 'Belgrade']);

        $this->get('/listings?city=Test_City')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $literal->id)
            );

        $this->get('/listings?city=%25')
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));
    }

    public function test_pagination_links_keep_every_active_filter(): void
    {
        Listing::factory()->count(13)->approved()->create([
            'bedrooms' => 3,
            'area_value' => 100,
            'amenities' => ['pool', 'garden'],
        ]);

        $this->get('/listings?minBedrooms=3&minArea=90&amenities[]=pool&amenities[]=garden')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.total', 13)
                ->where('listings.next_page_url', function (string $url): bool {
                    $query = [];
                    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

                    return $query['minBedrooms'] === '3'
                        && $query['minArea'] === '90'
                        && $query['amenities'] === ['pool', 'garden']
                        && $query['page'] === '2';
                })
            );

        $this->get('/listings?minBedrooms=3&minArea=90&amenities[]=pool&amenities[]=garden&page=2')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('filters.amenities', ['garden', 'pool'])
            );
    }

    public function test_apply_public_filters_works_with_stored_filters(): void
    {
        $match = Listing::factory()->approved()->create([
            'bedrooms' => 4,
            'amenities' => ['fireplace', 'garden'],
        ]);
        Listing::factory()->approved()->create([
            'bedrooms' => 4,
            'amenities' => ['garden'],
        ]);

        $ids = app(ListingService::class)
            ->applyPublicFilters(Listing::actuallyPubliclyVisible(), [
                'search' => '',
                'listingType' => '',
                'propertyType' => '',
                'city' => '',
                'minPrice' => '',
                'maxPrice' => '',
                'minBedrooms' => '4',
                'minBathrooms' => '',
                'minArea' => '',
                'maxArea' => '',
                'amenities' => ['garden', 'fireplace'],
                'sort' => 'newest',
            ])
            ->pluck('id')
            ->all();

        $this->assertSame([$match->id], $ids);
    }
}
