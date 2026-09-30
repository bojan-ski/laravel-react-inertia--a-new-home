<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingCountryTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Stone House by the Sea',
            'listing_type' => 'sale',
            'property_type' => 'house',
            'price' => 420000,
            'description' => 'A restored stone house a short walk from the harbour.',
            'country' => 'Croatia',
            'city' => 'Split',
        ], $overrides);
    }

    /**
     * @return array{User, Agency}
     */
    private function approvedAgencyOwner(): array
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        return [$user, $agency];
    }

    public function test_country_options_are_the_european_country_names(): void
    {
        $options = Listing::countryOptions();

        $this->assertCount(37, $options);
        $this->assertSame('Albania', $options[0]);
        $this->assertSame('United Kingdom', $options[36]);
        $this->assertContains('Bosnia and Herzegovina', $options);
        $this->assertTrue(array_is_list($options));
    }

    public function test_user_form_pages_receive_the_country_options(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->get('/my-listings/create')
            ->assertInertia(fn (Assert $page) => $page
                ->has('countryOptions', 37)
                ->where('countryOptions.5', 'Croatia'));

        $this->actingAs($user)
            ->get("/my-listings/{$listing->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->has('countryOptions', 37)
                ->where('countryOptions.5', 'Croatia'));
    }

    public function test_agency_form_pages_receive_the_country_options(): void
    {
        [$user, $agency] = $this->approvedAgencyOwner();
        $listing = Listing::factory()->pendingReview()->create(['user_id' => null, 'agency_id' => $agency->id]);

        $this->actingAs($user)
            ->get('/agency/listings/create')
            ->assertInertia(fn (Assert $page) => $page
                ->has('countryOptions', 37)
                ->where('countryOptions.5', 'Croatia'));

        $this->actingAs($user)
            ->get("/agency/listings/{$listing->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->has('countryOptions', 37)
                ->where('countryOptions.5', 'Croatia'));
    }

    public function test_listing_accepts_a_listed_country_and_stores_its_name(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->validPayload(['country' => 'North Macedonia']))
            ->assertRedirect('/profile')
            ->assertSessionHasNoErrors();

        $this->assertSame('North Macedonia', Listing::where('user_id', $user->id)->sole()->country);
    }

    public function test_user_listing_rejects_a_country_outside_the_list(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->post('/my-listings', $this->validPayload(['country' => 'United States']))
            ->assertSessionHasErrors('country');

        $this->assertSame(0, Listing::count());
    }

    public function test_country_codes_are_not_accepted(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->post('/my-listings', $this->validPayload(['country' => 'HR']))
            ->assertSessionHasErrors('country');
    }

    public function test_agency_listing_rejects_a_country_outside_the_list(): void
    {
        [$user] = $this->approvedAgencyOwner();

        $this->actingAs($user)
            ->post('/agency/listings', $this->validPayload(['country' => 'Atlantis']))
            ->assertSessionHasErrors('country');

        $this->assertSame(0, Listing::count());
    }

    public function test_update_rejects_a_legacy_country_until_it_is_repicked(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create(['country' => 'Lorem ipsum']);

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->validPayload(['country' => 'Lorem ipsum']))
            ->assertSessionHasErrors('country');

        $this->assertSame('Lorem ipsum', $listing->fresh()->country);

        $this->actingAs($user)
            ->put("/my-listings/{$listing->id}", $this->validPayload(['country' => 'Slovenia']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Slovenia', $listing->fresh()->country);
    }
}
