<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\ListingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function listing(int $approvedDaysAgo, array $overrides = [], string $state = 'approved'): Listing
    {
        return Listing::factory()->{$state}()->create(array_merge([
            'approved_at' => now()->subDays($approvedDaysAgo),
        ], $overrides));
    }

    /**
     * @return list<int>
     */
    private function latestIds(): array
    {
        return array_column(app(ListingService::class)->getLatestListings(), 'id');
    }

    public function test_a_guest_gets_the_landing_page_with_its_copy(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('landing', __('app.landing'))
                ->where('landing.hero.headline', 'Find your perfect new beginning.')
                ->where('landing.latest.per_month', '/mo')
                ->has('latestListings', 0)
            );
    }

    public function test_a_signed_in_user_gets_the_landing_page_with_the_latest_listings(): void
    {
        $listing = $this->listing(1);

        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('landing', __('app.landing'))
                ->has('latestListings', 1)
                ->where('latestListings.0.id', $listing->id)
            );
    }

    public function test_the_landing_copy_no_longer_carries_the_removed_sections(): void
    {
        $landing = __('app.landing');

        $this->assertSame(['title', 'hero', 'intro', 'features', 'latest', 'cta'], array_keys($landing));
        $this->assertArrayNotHasKey('eyebrow', $landing['features']);
        $this->assertArrayNotHasKey('features', __('app.nav'));
        $this->assertArrayNotHasKey('how_it_works', __('app.nav'));
    }

    public function test_the_three_newest_visible_listings_come_back_newest_first(): void
    {
        $oldest = $this->listing(40);
        $newest = $this->listing(1);
        $third = $this->listing(20);
        $second = $this->listing(5);

        $this->assertSame([$newest->id, $second->id, $third->id], $this->latestIds());
        $this->assertNotContains($oldest->id, $this->latestIds());
    }

    public function test_fewer_visible_listings_return_fewer_cards(): void
    {
        $older = $this->listing(8);
        $newer = $this->listing(2);

        $this->assertSame([$newer->id, $older->id], $this->latestIds());
    }

    public function test_listings_that_are_not_publicly_visible_never_appear(): void
    {
        $visible = $this->listing(30);

        $this->listing(1, [], 'pendingReview');
        $this->listing(1, [], 'expired');
        $this->listing(1, [], 'sold');
        $this->listing(1, [], 'rented');
        $this->listing(1, ['approved_at' => now()->addDay()]);
        $this->listing(1, ['user_id' => User::factory()->regularUser()->suspended()]);
        $this->listing(1, [
            'user_id' => null,
            'agency_id' => Agency::factory()->approved()->create([
                'user_id' => User::factory()->agency()->suspended(),
            ])->id,
        ]);

        $this->assertSame([$visible->id], $this->latestIds());
    }

    public function test_the_image_is_the_first_large_image_by_sort_order(): void
    {
        $withImages = $this->listing(1);
        $withoutImages = $this->listing(2);
        ListingImage::factory()->create(['listing_id' => $withImages->id, 'sort_order' => 2]);
        $first = ListingImage::factory()->create(['listing_id' => $withImages->id, 'sort_order' => 0]);
        ListingImage::factory()->create(['listing_id' => $withImages->id, 'sort_order' => 1]);

        [$withImagesCard, $withoutImagesCard] = app(ListingService::class)->getLatestListings();

        $this->assertSame([$withImages->id, $withoutImages->id], [$withImagesCard['id'], $withoutImagesCard['id']]);
        $this->assertStringEndsWith($first->large_path, $withImagesCard['imageUrl']);
        $this->assertStringContainsString('listings/large/', $withImagesCard['imageUrl']);
        $this->assertNull($withoutImagesCard['imageUrl']);
    }

    public function test_the_payload_exposes_exactly_the_latest_card_keys(): void
    {
        $listing = $this->listing(1, [
            'listing_type' => 'rent',
            'price' => 2400,
            'city' => 'Kotor',
            'country' => 'Montenegro',
        ]);

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->has('latestListings.0', fn (Assert $card) => $card
                    ->where('id', $listing->id)
                    ->where('title', $listing->title)
                    ->where('slug', $listing->slug)
                    ->where('listingType', 'rent')
                    ->where('price', $listing->fresh()->price)
                    ->where('currency', 'EUR')
                    ->where('city', 'Kotor')
                    ->where('country', 'Montenegro')
                    ->where('imageUrl', null)
                )
            );

        $this->assertSame(
            ['id', 'title', 'slug', 'listingType', 'price', 'currency', 'city', 'country', 'imageUrl'],
            array_keys(app(ListingService::class)->getLatestListings()[0]),
        );
    }

    public function test_the_query_count_does_not_grow_with_listings_and_images(): void
    {
        $countQueries = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            app(ListingService::class)->getLatestListings();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $first = $this->listing(1);
        ListingImage::factory()->create(['listing_id' => $first->id]);
        $withOne = $countQueries();

        foreach ([2, 3] as $daysAgo) {
            $listing = $this->listing($daysAgo);
            ListingImage::factory()->count(3)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])
                ->create(['listing_id' => $listing->id]);
        }

        $this->assertCount(3, $this->latestIds());
        $this->assertSame($withOne, $countQueries());
    }
}
