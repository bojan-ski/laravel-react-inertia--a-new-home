<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingMarketLockTest extends TestCase
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
            'currency' => 'USD',
            'description' => 'Bright apartment by the river.',
            'country' => 'Austria',
            'city' => 'Vienna',
            'bedrooms' => 2,
            'bathrooms' => 1,
            'area_value' => 84,
            'area_unit' => 'sqft',
        ], $overrides);
    }

    private function approvedAgencyOwner(): User
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id]);

        return $user->refresh();
    }

    public function test_currency_and_area_unit_are_not_mass_assignable(): void
    {
        $fillable = (new Listing)->getFillable();

        $this->assertNotContains('currency', $fillable);
        $this->assertNotContains('area_unit', $fillable);
    }

    public function test_new_listing_defaults_to_eur_and_square_metres(): void
    {
        $listing = new Listing;

        $this->assertSame('EUR', $listing->currency);
        $this->assertSame('m²', $listing->area_unit);
    }

    public function test_regular_user_create_ignores_posted_currency_and_unit(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->payload())
            ->assertSessionHasNoErrors();

        $listing = Listing::sole();
        $this->assertSame('EUR', $listing->currency);
        $this->assertSame('m²', $listing->area_unit);
    }

    public function test_regular_user_update_ignores_posted_currency_and_unit_and_normalizes_legacy_values(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'area_unit' => 'sqft',
        ]);

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->payload(['currency' => 'GBP']))
            ->assertSessionHasNoErrors();

        $listing->refresh();
        $this->assertSame('EUR', $listing->currency);
        $this->assertSame('m²', $listing->area_unit);
    }

    public function test_agency_create_ignores_posted_currency_and_unit(): void
    {
        $user = $this->approvedAgencyOwner();

        $this->actingAs($user)
            ->post('/agency/listings', $this->payload())
            ->assertSessionHasNoErrors();

        $listing = Listing::sole();
        $this->assertSame('EUR', $listing->currency);
        $this->assertSame('m²', $listing->area_unit);
    }

    public function test_agency_update_ignores_posted_currency_and_unit_and_normalizes_legacy_values(): void
    {
        $user = $this->approvedAgencyOwner();
        $listing = Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $user->agency->id,
            'currency' => 'GBP',
            'area_unit' => 'sqft',
        ]);

        $this->actingAs($user)
            ->put("/agency/listings/{$listing->id}", $this->payload())
            ->assertSessionHasNoErrors();

        $listing->refresh();
        $this->assertSame('EUR', $listing->currency);
        $this->assertSame('m²', $listing->area_unit);
    }

    public function test_listing_form_no_longer_offers_a_currency_label(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/my-listings/create')
            ->assertInertia(fn (Assert $page) => $page
                ->missing('translations.fields.currency')
                ->where('translations.fields.price', __('app.create_listing.fields.price'))
                ->where('translations.fields.area', __('app.create_listing.fields.area')));
    }

    public function test_payloads_still_carry_currency_and_area_unit(): void
    {
        $listing = Listing::factory()->approved()->create();

        $card = $listing->toCardArray();
        $this->assertSame('EUR', $card['currency']);
        $this->assertSame('m²', $card['areaUnit']);

        $form = $listing->toFormArray();
        $this->assertSame('EUR', $form['currency']);
        $this->assertSame('m²', $form['areaUnit']);
    }

    public function test_data_migration_converts_sqft_fills_missing_units_and_relabels_currency(): void
    {
        $sqft = Listing::factory()->approved()->create([
            'currency' => 'GBP',
            'area_value' => 1000,
            'area_unit' => 'sqft',
        ]);
        $noUnit = Listing::factory()->pendingReview()->create([
            'currency' => 'USD',
            'price' => 420000,
            'area_value' => 75,
            'area_unit' => null,
        ]);
        $trashed = Listing::factory()->rejected()->create(['area_value' => 500, 'area_unit' => 'sqft']);
        $trashed->delete();
        $untouched = Listing::factory()->approved()->create(['area_value' => 84]);

        $migration = require database_path('migrations/2026_09_28_160105_normalize_listing_currency_and_area_unit.php');
        $migration->up();

        $rows = DB::table('listings')->get()->keyBy('id');

        $this->assertSame('92.90', $rows[$sqft->id]->area_value);
        $this->assertSame('m²', $rows[$sqft->id]->area_unit);
        $this->assertSame('EUR', $rows[$sqft->id]->currency);
        $this->assertSame('approved', $rows[$sqft->id]->status);

        $this->assertSame('75.00', $rows[$noUnit->id]->area_value);
        $this->assertSame('m²', $rows[$noUnit->id]->area_unit);
        $this->assertSame('EUR', $rows[$noUnit->id]->currency);
        $this->assertSame('420000.00', $rows[$noUnit->id]->price);
        $this->assertSame('pending_review', $rows[$noUnit->id]->status);

        $this->assertSame('46.45', $rows[$trashed->id]->area_value);
        $this->assertSame('m²', $rows[$trashed->id]->area_unit);

        $this->assertSame('84.00', $rows[$untouched->id]->area_value);

        $this->assertSame(0, DB::table('listings')->where('area_unit', '<>', 'm²')->orWhereNull('area_unit')->count());
        $this->assertSame(0, DB::table('listings')->where('currency', '<>', 'EUR')->count());
    }
}
