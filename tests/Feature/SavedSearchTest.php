<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'listingType' => 'rent',
            'propertyType' => 'apartment',
            'city' => 'Belgrade',
            'minBedrooms' => '2',
        ], $overrides);
    }

    // ---- Saving ----------------------------------------------------------

    public function test_user_can_save_the_current_filters_without_sort(): void
    {
        $this->freezeSecond();
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->from('/listings?listingType=rent')
            ->post('/saved-searches', $this->payload([
                'amenities' => ['pool', 'parking'],
                'minPrice' => ' 100000 ',
                'sort' => 'price_low_high',
                'name' => 'Family flats',
            ]))
            ->assertRedirect('/listings?listingType=rent')
            ->assertSessionHas('success', __('app.saved_searches.flash.saved'));

        $search = SavedSearch::sole();

        $this->assertSame($user->id, $search->user_id);
        $this->assertSame('Family flats', $search->name);
        $this->assertTrue($search->alerts_enabled);
        $this->assertTrue($search->last_alerted_at->equalTo(now()));
        $this->assertEquals([
            'search' => '',
            'listingType' => 'rent',
            'propertyType' => 'apartment',
            'city' => 'Belgrade',
            'minPrice' => '100000',
            'maxPrice' => '',
            'minBedrooms' => '2',
            'minBathrooms' => '',
            'minArea' => '',
            'maxArea' => '',
            'amenities' => ['parking', 'pool'],
        ], $search->filters);
        $this->assertArrayNotHasKey('sort', $search->filters);
    }

    public function test_agency_user_can_save_a_search(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post('/saved-searches', $this->payload())
            ->assertSessionHas('success');

        $this->assertDatabaseCount('saved_searches', 1);
    }

    public function test_blank_name_gets_an_auto_name_from_the_filters(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->post('/saved-searches', $this->payload(['name' => '  ']));

        $this->assertSame('Rent · Apartment · Belgrade · 2+ beds', SavedSearch::sole()->name);
    }

    public function test_search_can_be_saved_with_alerts_off(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->post('/saved-searches', $this->payload(['alerts_enabled' => false]));

        $this->assertFalse(SavedSearch::sole()->alerts_enabled);
    }

    public function test_empty_filter_set_is_rejected(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/saved-searches', ['sort' => 'price_high_low', 'search' => '   ', 'name' => 'Everything'])
            ->assertRedirect()
            ->assertSessionHas('error', __('app.saved_searches.errors.empty'));

        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_duplicate_filter_set_is_rejected(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->post('/saved-searches', $this->payload(['amenities' => ['pool', 'parking']]));

        $this->actingAs($user)
            ->post('/saved-searches', $this->payload(['amenities' => ['parking', 'pool'], 'sort' => 'oldest', 'name' => 'Other name']))
            ->assertSessionHas('error', __('app.saved_searches.errors.duplicate'));

        $this->assertDatabaseCount('saved_searches', 1);
    }

    public function test_same_filters_saved_by_another_user_are_not_a_duplicate(): void
    {
        $other = User::factory()->regularUser()->create();
        SavedSearch::factory()->for($other)->withFilters($this->payload())->create();
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/saved-searches', $this->payload())
            ->assertSessionHas('success');

        $this->assertDatabaseCount('saved_searches', 2);
    }

    public function test_eleventh_search_is_rejected(): void
    {
        $user = User::factory()->regularUser()->create();

        foreach (range(1, 10) as $price) {
            SavedSearch::factory()->for($user)->withFilters(['minPrice' => (string) ($price * 1000)])->create();
        }

        $this->actingAs($user)
            ->post('/saved-searches', $this->payload())
            ->assertSessionHas('error', 'You can save up to 10 searches. Delete one to save a new search.');

        $this->assertSame(10, $user->savedSearches()->count());
    }

    public function test_invalid_filters_fail_validation(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/saved-searches', $this->payload(['minPrice' => '500', 'maxPrice' => '100', 'amenities' => ['jacuzzi']]))
            ->assertSessionHasErrors(['maxPrice', 'amenities.0']);

        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_name_longer_than_100_characters_fails_validation(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/saved-searches', $this->payload(['name' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_admin_cannot_save_a_search(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/saved-searches', $this->payload())->assertForbidden();

        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post('/saved-searches', $this->payload())->assertRedirect('/login');
        $this->get('/saved-searches')->assertRedirect('/login');

        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_suspended_user_is_logged_out_and_redirected(): void
    {
        $user = User::factory()->suspended()->create();

        $this->actingAs($user)->post('/saved-searches', $this->payload())->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_saving_is_rate_limited_per_user(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = SavedSearch::factory()->for($user)->create();

        foreach (range(1, 20) as $attempt) {
            $this->actingAs($user)->patch("/saved-searches/{$search->id}", ['name' => "Name {$attempt}"]);
        }

        $this->actingAs($user)->post('/saved-searches', $this->payload())->assertTooManyRequests();

        $this->assertDatabaseCount('saved_searches', 1);
    }

    // ---- Listings page ---------------------------------------------------

    public function test_listings_page_passes_save_search_props(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->get('/listings?listingType=rent&propertyType=apartment&city=Belgrade&minBedrooms=2')
            ->assertInertia(fn (Assert $page) => $page
                ->where('canSaveSearch', true)
                ->where('suggestedSearchName', 'Rent · Apartment · Belgrade · 2+ beds')
                ->where('saveSearchTranslations', __('app.saved_searches.save'))
            );
    }

    public function test_guest_can_see_the_save_button_but_admin_cannot(): void
    {
        $this->get('/listings')->assertInertia(fn (Assert $page) => $page->where('canSaveSearch', true));

        $this->actingAs(User::factory()->admin()->create())
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('canSaveSearch', false));
    }

    // ---- Managing --------------------------------------------------------

    public function test_index_lists_only_the_users_own_searches(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = SavedSearch::factory()->for($user)->withFilters([
            'listingType' => 'sale',
            'minPrice' => '100000',
            'maxPrice' => '300000',
            'amenities' => ['pool'],
        ])->create(['name' => 'Villas']);
        SavedSearch::factory()->create();

        $this->actingAs($user)
            ->get('/saved-searches')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SavedSearches/Index')
                ->has('searches', 1)
                ->where('maxSearches', 10)
                ->where('searches.0', [
                    'id' => $search->id,
                    'name' => 'Villas',
                    'filters' => [
                        'listingType' => 'sale',
                        'minPrice' => '100000',
                        'maxPrice' => '300000',
                        'amenities' => ['pool'],
                    ],
                    'alertsEnabled' => true,
                    'createdAt' => $search->created_at->toIso8601String(),
                    'url' => route('listings.index', [
                        'listingType' => 'sale',
                        'minPrice' => '100000',
                        'maxPrice' => '300000',
                        'amenities' => ['pool'],
                    ]),
                    'summary' => ['Buy', '€100,000–€300,000', 'Swimming pool'],
                ])
                ->where('translations.title', __('app.saved_searches.title'))
            );
    }

    public function test_admin_cannot_open_the_saved_searches_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/saved-searches')->assertForbidden();
    }

    public function test_bookmarks_page_links_to_saved_searches(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->where('canManageSavedSearches', true)
                ->where('translations.saved_searches_link', __('app.bookmarks.saved_searches_link'))
            );
    }

    public function test_owner_can_rename_a_search(): void
    {
        $search = SavedSearch::factory()->create(['name' => 'Old']);

        $this->actingAs($search->user)
            ->patch("/saved-searches/{$search->id}", ['name' => '  New name  '])
            ->assertSessionHas('success', __('app.saved_searches.flash.updated'));

        $this->assertSame('New name', $search->fresh()->name);
    }

    public function test_rename_requires_a_name(): void
    {
        $search = SavedSearch::factory()->create(['name' => 'Old']);

        $this->actingAs($search->user)
            ->patch("/saved-searches/{$search->id}", ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame('Old', $search->fresh()->name);
    }

    public function test_owner_can_turn_alerts_off(): void
    {
        $search = SavedSearch::factory()->create();

        $this->actingAs($search->user)->patch("/saved-searches/{$search->id}", ['alerts_enabled' => false]);

        $this->assertFalse($search->fresh()->alerts_enabled);
    }

    public function test_re_enabling_alerts_resets_the_alert_mark(): void
    {
        $this->freezeSecond();
        $search = SavedSearch::factory()->alertsDisabled()->create(['last_alerted_at' => now()->subDays(20)]);

        $this->actingAs($search->user)->patch("/saved-searches/{$search->id}", ['alerts_enabled' => true]);

        $search->refresh();
        $this->assertTrue($search->alerts_enabled);
        $this->assertTrue($search->last_alerted_at->equalTo(now()));
    }

    public function test_renaming_leaves_the_alert_mark_alone(): void
    {
        $this->freezeSecond();
        $search = SavedSearch::factory()->create(['last_alerted_at' => now()->subDays(3)]);

        $this->actingAs($search->user)->patch("/saved-searches/{$search->id}", ['name' => 'New', 'alerts_enabled' => true]);

        $this->assertTrue($search->fresh()->last_alerted_at->equalTo(now()->subDays(3)));
    }

    public function test_owner_can_delete_a_search(): void
    {
        $search = SavedSearch::factory()->create();

        $this->actingAs($search->user)
            ->delete("/saved-searches/{$search->id}")
            ->assertRedirect('/saved-searches')
            ->assertSessionHas('success', __('app.saved_searches.flash.deleted'));

        $this->assertModelMissing($search);
    }

    public function test_other_users_cannot_update_or_delete_a_search(): void
    {
        $search = SavedSearch::factory()->create(['name' => 'Mine']);
        $other = User::factory()->regularUser()->create();

        $this->actingAs($other)->patch("/saved-searches/{$search->id}", ['name' => 'Theirs'])->assertForbidden();
        $this->actingAs($other)->patch("/saved-searches/{$search->id}", ['alerts_enabled' => false])->assertForbidden();
        $this->actingAs($other)->delete("/saved-searches/{$search->id}")->assertForbidden();

        $search->refresh();
        $this->assertSame('Mine', $search->name);
        $this->assertTrue($search->alerts_enabled);
    }

    public function test_account_deletion_removes_saved_searches(): void
    {
        Notification::fake();
        $user = User::factory()->regularUser()->create();
        SavedSearch::factory()->for($user)->count(2)->create();
        $kept = SavedSearch::factory()->create();

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect();

        $this->assertDatabaseCount('saved_searches', 1);
        $this->assertModelExists($kept);
    }
}
