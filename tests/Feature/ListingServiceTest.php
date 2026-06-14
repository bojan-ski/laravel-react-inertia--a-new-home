<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ListingServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private ListingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ListingService;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
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

    public function test_create_produces_a_listing_owned_by_the_passed_user(): void
    {
        $owner = User::factory()->regularUser()->create();

        $listing = $this->service->create($owner, $this->validData());

        $this->assertTrue($listing->exists);
        $this->assertSame($owner->id, $listing->user_id);
        $this->assertSame('Modern City Apartment', $listing->title);
    }

    public function test_create_generates_unique_slugs_for_duplicate_titles(): void
    {
        $owner = User::factory()->regularUser()->create();

        $first = $this->service->create($owner, $this->validData(['title' => 'Garden Townhouse']));
        $second = $this->service->create($owner, $this->validData(['title' => 'Garden Townhouse']));
        $third = $this->service->create($owner, $this->validData(['title' => 'Garden Townhouse']));

        $this->assertSame('garden-townhouse', $first->slug);
        $this->assertSame('garden-townhouse-2', $second->slug);
        $this->assertSame('garden-townhouse-3', $third->slug);
    }

    public function test_create_forces_moderation_defaults_even_with_conflicting_data(): void
    {
        $owner = User::factory()->regularUser()->create();

        $listing = $this->service->create($owner, $this->validData([
            'agency_id' => 999,
            'status' => ListingStatus::Approved->value,
            'approved_at' => now(),
            'rejected_at' => now(),
            'rejection_reason' => 'Spoofed reason.',
        ]));

        $this->assertNull($listing->agency_id);
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
        $this->assertNull($listing->rejected_at);
        $this->assertNull($listing->rejection_reason);
        $this->assertSame($owner->id, $listing->user_id);
    }

    public function test_update_throws_for_a_non_editable_status(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->rejected()->for($owner, 'owner')->create();

        $this->expectException(InvalidListingStatusTransitionException::class);

        $this->service->update($listing, $this->validData());
    }

    public function test_update_preserves_user_id(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($owner, 'owner')->create();

        $updated = $this->service->update($listing, $this->validData());

        $this->assertSame($owner->id, $updated->user_id);
    }

    public function test_update_forces_agency_id_to_null(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($owner, 'owner')->create();

        $updated = $this->service->update($listing, $this->validData(['agency_id' => 999]));

        $this->assertNull($updated->agency_id);
    }

    public function test_update_does_not_touch_rejected_at_or_rejection_reason(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->hidden()->for($owner, 'owner')->create([
            'rejected_at' => now(),
            'rejection_reason' => 'Stale reason.',
        ]);

        $updated = $this->service->update($listing, $this->validData([
            'rejected_at' => null,
            'rejection_reason' => 'Spoofed reason.',
        ]));

        $this->assertNotNull($updated->rejected_at);
        $this->assertSame('Stale reason.', $updated->rejection_reason);
    }
}
