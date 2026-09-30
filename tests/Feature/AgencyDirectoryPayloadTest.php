<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencyDirectoryPayloadTest extends TestCase
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

    public function test_listings_count_only_counts_the_agencys_publicly_visible_listings(): void
    {
        $agency = Agency::factory()->approved()->create();

        $this->agencyListing($agency);
        $this->agencyListing($agency, 'pendingReview');
        $this->agencyListing($agency, 'hidden');
        $this->agencyListing($agency, 'sold');
        $this->agencyListing($agency, 'rented');
        $this->agencyListing($agency, 'expired');
        $this->agencyListing($agency, 'approved', ['approved_at' => now()->addDay()]);
        $this->agencyListing($agency, 'rejected')->delete();
        $this->agencyListing($agency)->delete();
        $this->agencyListing(Agency::factory()->approved()->create());

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agencies.data', fn ($agencies) => collect($agencies)->firstWhere('id', $agency->id)['listingsCount'] === 1)
            );
    }

    public function test_an_agency_without_listings_has_a_zero_count(): void
    {
        Agency::factory()->approved()->create();

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.listingsCount', 0)
            );
    }

    public function test_the_count_survives_a_search(): void
    {
        $agency = Agency::factory()->approved()->create(['name' => 'Harbor Homes']);
        Agency::factory()->approved()->create(['name' => 'Summit Estates']);
        $this->agencyListing($agency);
        $this->agencyListing($agency);

        $this->get('/agencies?search=Harbor')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $agency->id)
                ->where('agencies.data.0.listingsCount', 2)
            );
    }

    public function test_the_count_survives_page_two(): void
    {
        Agency::factory()->count(12)->approved()->create(['approved_at' => now()->subDay()]);
        $oldest = Agency::factory()->approved()->create(['approved_at' => now()->subMonth()]);
        $this->agencyListing($oldest);
        $this->agencyListing($oldest);
        $this->agencyListing($oldest);

        $this->get('/agencies?page=2')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $oldest->id)
                ->where('agencies.data.0.listingsCount', 3)
            );
    }

    public function test_the_query_count_does_not_grow_with_agencies_and_listings(): void
    {
        $countQueries = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->get('/agencies')->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        foreach (range(1, 2) as $index) {
            $this->agencyListing(Agency::factory()->approved()->create());
        }
        $withTwo = $countQueries();

        foreach (range(1, 4) as $index) {
            $agency = Agency::factory()->approved()->create();
            $this->agencyListing($agency);
            $this->agencyListing($agency);
        }

        $this->assertSame(6, Agency::count());
        $this->assertSame($withTwo, $countQueries());
    }

    public function test_directory_translations_carry_the_new_keys(): void
    {
        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->where('translations.count_one', __('app.agencies.count_one'))
                ->where('translations.count_other', __('app.agencies.count_other'))
                ->where('translations.properties_one', __('app.agencies.properties_one'))
                ->where('translations.properties_other', __('app.agencies.properties_other'))
                ->where('translations.search.label', __('app.agencies.search.label'))
                ->missing('translations.count_prefix')
                ->missing('translations.count_suffix')
            );
    }

    public function test_the_profile_payload_does_not_carry_a_listings_count(): void
    {
        $agency = Agency::factory()->approved()->create();

        $this->get("/agencies/{$agency->slug}")
            ->assertInertia(fn (Assert $page) => $page->missing('agency.listingsCount'));
    }
}
