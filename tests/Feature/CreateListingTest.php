<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateListingTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Modern City Apartment',
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => 285000,
            'currency' => 'EUR',
            'description' => 'A bright two-bedroom apartment in the city centre.',
            'country' => 'Serbia',
            'city' => 'Belgrade',
        ], $overrides);
    }

    public function test_regular_user_can_view_the_create_page(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/my-listings/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Listings/Create'));
    }

    public function test_regular_user_can_submit_a_valid_listing(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->validPayload())
            ->assertRedirect('/profile')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('listings', [
            'title' => 'Modern City Apartment',
            'user_id' => $user->id,
        ]);
    }

    public function test_created_listing_has_correct_owner_status_and_null_agency_id(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->post('/my-listings', $this->validPayload());

        $listing = Listing::firstOrFail();

        $this->assertSame($user->id, $listing->user_id);
        $this->assertNull($listing->agency_id);
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
        $this->assertNull($listing->rejected_at);
        $this->assertNull($listing->rejection_reason);
    }

    public function test_slug_is_generated_and_unique_on_collision(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->post('/my-listings', $this->validPayload(['title' => 'Seaside Villa']));
        $this->actingAs($user)->post('/my-listings', $this->validPayload(['title' => 'Seaside Villa']));

        $slugs = Listing::orderBy('id')->pluck('slug')->all();

        $this->assertSame(['seaside-villa', 'seaside-villa-2'], $slugs);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/my-listings/create')->assertRedirect('/login');
        $this->post('/my-listings', $this->validPayload())->assertRedirect('/login');
    }

    public function test_agency_user_cannot_access(): void
    {
        $agency = User::factory()->agency()->create();

        $this->actingAs($agency)->get('/my-listings/create')->assertForbidden();
        $this->actingAs($agency)->post('/my-listings', $this->validPayload())->assertForbidden();
    }

    public function test_admin_user_cannot_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/my-listings/create')->assertForbidden();
        $this->actingAs($admin)->post('/my-listings', $this->validPayload())->assertForbidden();
    }

    public function test_suspended_user_cannot_access(): void
    {
        $suspended = User::factory()->regularUser()->suspended()->create();

        $this->actingAs($suspended)->get('/my-listings/create')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_validation_fails_for_missing_required_fields(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', [])
            ->assertSessionHasErrors([
                'title', 'listing_type', 'property_type', 'price',
                'currency', 'description', 'country', 'city',
            ]);

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_validation_rejects_invalid_listing_type(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->validPayload(['listing_type' => 'lease']))
            ->assertSessionHasErrors('listing_type');
    }

    public function test_validation_rejects_invalid_property_type(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->validPayload(['property_type' => 'mansion']))
            ->assertSessionHasErrors('property_type');
    }

    public function test_validation_rejects_year_built_out_of_range(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->validPayload(['year_built' => 1500]))
            ->assertSessionHasErrors('year_built');
    }

    public function test_server_controlled_fields_cannot_be_set_by_request_payload(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->post('/my-listings', $this->validPayload([
            'agency_id' => 999,
            'status' => ListingStatus::Approved->value,
            'approved_at' => now()->toDateTimeString(),
            'user_id' => 12345,
        ]));

        $listing = Listing::firstOrFail();

        $this->assertSame($user->id, $listing->user_id);
        $this->assertNull($listing->agency_id);
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
    }
}
