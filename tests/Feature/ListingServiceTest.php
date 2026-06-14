<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
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
}
