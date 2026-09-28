<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Consolidated coverage of the listing status state machine.
 *
 * Every allowed transition and every blocked transition is exercised here as a
 * single coherent suite. Invalid transitions are asserted at BOTH layers: the
 * controller/policy denial (403) and the service-level exception (thrown when
 * the policy is bypassed). See property-22-listing-status-workflow-spec.
 */
class ListingWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function regularOwner(): User
    {
        return User::factory()->regularUser()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Workflow City Apartment',
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => 275000,
            'currency' => 'EUR',
            'description' => 'A detailed description of the property.',
            'country' => 'Serbia',
            'city' => 'Belgrade',
        ], $overrides);
    }

    // ---------------------------------------------------------------------
    // pending_review
    // ---------------------------------------------------------------------

    public function test_pending_listing_can_be_approved_by_admin(): void
    {
        Notification::fake();

        $listing = Listing::factory()->pendingReview()->for($this->regularOwner(), 'owner')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('success');

        $listing->refresh();

        $this->assertSame(ListingStatus::Approved, $listing->status);
        $this->assertNotNull($listing->approved_at);
    }

    public function test_pending_listing_can_be_rejected_by_admin(): void
    {
        Notification::fake();

        $listing = Listing::factory()->pendingReview()->for($this->regularOwner(), 'owner')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/reject", ['reason' => 'Photos do not match the description.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $listing = Listing::withTrashed()->find($listing->id);

        $this->assertSame(ListingStatus::Rejected, $listing->status);
        $this->assertSame('Photos do not match the description.', $listing->rejection_reason);
        $this->assertNotNull($listing->rejected_at);
        $this->assertNull($listing->approved_at);
        $this->assertNotNull($listing->deleted_at);
    }

    public function test_pending_listing_can_be_edited_by_owner_and_stays_pending(): void
    {
        $owner = $this->regularOwner();
        $listing = Listing::factory()->pendingReview()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->put("/my-listings/{$listing->id}", $this->validPayload())
            ->assertRedirect('/profile');

        $listing->refresh();

        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
    }

    public function test_pending_listing_can_be_deleted_by_owner(): void
    {
        $owner = $this->regularOwner();
        $listing = Listing::factory()->pendingReview()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->delete("/my-listings/{$listing->id}")
            ->assertRedirect('/profile');

        $this->assertSoftDeleted($listing);
    }

    // ---------------------------------------------------------------------
    // approved
    // ---------------------------------------------------------------------

    public function test_approved_listing_can_be_edited_by_owner_and_reverts_to_pending(): void
    {
        $owner = $this->regularOwner();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->assertNotNull($listing->approved_at);

        $this->actingAs($owner)
            ->put("/my-listings/{$listing->id}", $this->validPayload())
            ->assertRedirect('/profile');

        $listing->refresh();

        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
    }

    public function test_approved_listing_can_be_deleted_by_owner(): void
    {
        $owner = $this->regularOwner();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->delete("/my-listings/{$listing->id}")
            ->assertRedirect('/profile');

        $this->assertSoftDeleted($listing);
    }

    public function test_approved_listing_cannot_be_approved_again_via_moderation(): void
    {
        $listing = Listing::factory()->approved()->for($this->regularOwner(), 'owner')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertForbidden();

        $this->expectException(InvalidListingStatusTransitionException::class);

        (new ListingService)->approve($listing);
    }

    public function test_approved_listing_cannot_be_rejected_via_moderation(): void
    {
        $listing = Listing::factory()->approved()->for($this->regularOwner(), 'owner')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/reject", ['reason' => 'A valid reason.'])
            ->assertForbidden();

        $this->expectException(InvalidListingStatusTransitionException::class);

        (new ListingService)->reject($listing, 'A valid reason.');
    }

    // ---------------------------------------------------------------------
    // rejected
    // ---------------------------------------------------------------------

    public function test_rejected_listing_cannot_be_edited_by_owner(): void
    {
        $owner = $this->regularOwner();
        $listing = Listing::factory()->rejected()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->put("/my-listings/{$listing->id}", $this->validPayload())
            ->assertForbidden();

        $this->expectException(InvalidListingStatusTransitionException::class);

        (new ListingService)->update($listing, $this->validPayload());
    }

    public function test_rejected_listing_can_be_deleted_by_owner(): void
    {
        $owner = $this->regularOwner();
        $listing = Listing::factory()->rejected()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->delete("/my-listings/{$listing->id}")
            ->assertRedirect('/profile');

        $this->assertSoftDeleted($listing);
    }

    // ---------------------------------------------------------------------
    // hidden
    // ---------------------------------------------------------------------

    public function test_hidden_listing_can_be_edited_by_owner_and_stays_hidden(): void
    {
        $owner = $this->regularOwner();
        $listing = Listing::factory()->hidden()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->put("/my-listings/{$listing->id}", $this->validPayload())
            ->assertRedirect('/profile');

        $listing->refresh();

        $this->assertSame(ListingStatus::Hidden, $listing->status);
        $this->assertNull($listing->approved_at);
    }

    public function test_hidden_listing_can_be_deleted_by_owner(): void
    {
        $owner = $this->regularOwner();
        $listing = Listing::factory()->hidden()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->delete("/my-listings/{$listing->id}")
            ->assertRedirect('/profile');

        $this->assertSoftDeleted($listing);
    }

    public function test_hidden_listing_cannot_be_approved_via_moderation(): void
    {
        $listing = Listing::factory()->hidden()->for($this->regularOwner(), 'owner')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertForbidden();

        $this->expectException(InvalidListingStatusTransitionException::class);

        (new ListingService)->approve($listing);
    }

    public function test_hidden_listing_cannot_be_rejected_via_moderation(): void
    {
        $listing = Listing::factory()->hidden()->for($this->regularOwner(), 'owner')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listings/{$listing->id}/reject", ['reason' => 'A valid reason.'])
            ->assertForbidden();

        $this->expectException(InvalidListingStatusTransitionException::class);

        (new ListingService)->reject($listing, 'A valid reason.');
    }

    public function test_approved_listing_can_be_closed_by_owner(): void
    {
        $owner = $this->regularOwner();
        $sale = Listing::factory()->approved()->for($owner, 'owner')->create(['listing_type' => 'sale']);
        $rent = Listing::factory()->approved()->for($owner, 'owner')->create(['listing_type' => 'rent']);

        $this->actingAs($owner)->post("/my-listings/{$sale->id}/close")->assertRedirect();
        $this->actingAs($owner)->post("/my-listings/{$rent->id}/close")->assertRedirect();

        $this->assertSame(ListingStatus::Sold, $sale->refresh()->status);
        $this->assertSame(ListingStatus::Rented, $rent->refresh()->status);
    }

    public function test_closed_listings_cannot_be_edited_by_owner(): void
    {
        $owner = $this->regularOwner();

        foreach (['sold', 'rented'] as $state) {
            $listing = Listing::factory()->{$state}()->for($owner, 'owner')->create();

            $this->actingAs($owner)
                ->put("/my-listings/{$listing->id}", $this->validPayload())
                ->assertForbidden();
        }

        $this->expectException(InvalidListingStatusTransitionException::class);

        (new ListingService)->update(Listing::factory()->sold()->create(), $this->validPayload());
    }

    public function test_closed_listings_cannot_be_moderated(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['sold', 'rented'] as $state) {
            $listing = Listing::factory()->{$state}()->for($this->regularOwner(), 'owner')->create();

            $this->actingAs($admin)->post("/admin/listings/{$listing->id}/approve")->assertForbidden();
            $this->actingAs($admin)
                ->post("/admin/listings/{$listing->id}/reject", ['reason' => 'A valid reason.'])
                ->assertForbidden();
        }

        $this->expectException(InvalidListingStatusTransitionException::class);

        (new ListingService)->approve(Listing::factory()->rented()->create());
    }

    public function test_closed_listings_can_be_deleted_by_owner(): void
    {
        $owner = $this->regularOwner();

        foreach (['sold', 'rented'] as $state) {
            $listing = Listing::factory()->{$state}()->for($owner, 'owner')->create();

            $this->actingAs($owner)
                ->delete("/my-listings/{$listing->id}")
                ->assertRedirect('/profile');

            $this->assertSoftDeleted($listing);
        }
    }

    public function test_closed_listing_can_be_reopened_by_owner_without_review(): void
    {
        $owner = $this->regularOwner();
        $listing = Listing::factory()->rented()->for($owner, 'owner')->create();

        $this->actingAs($owner)->post("/my-listings/{$listing->id}/reopen")->assertRedirect();

        $listing->refresh();

        $this->assertSame(ListingStatus::Approved, $listing->status);
        $this->assertNotNull($listing->approved_at);
        $this->assertNull($listing->closed_at);
    }

    // ---------------------------------------------------------------------
    // soft-delete is orthogonal to status
    // ---------------------------------------------------------------------

    public function test_soft_deleted_listing_is_excluded_from_public_listings_page(): void
    {
        $visible = Listing::factory()->approved()->for($this->regularOwner(), 'owner')->create();

        $trashed = Listing::factory()->approved()->for($this->regularOwner(), 'owner')->create();
        $trashed->delete();

        $this->get('/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $visible->id)
            );
    }

    public function test_soft_deleted_listings_of_any_status_are_excluded_from_owner_dashboard(): void
    {
        $owner = $this->regularOwner();

        $live = Listing::factory()->pendingReview()->for($owner, 'owner')->create();

        foreach (['approved', 'pendingReview', 'hidden', 'rejected'] as $state) {
            Listing::factory()->{$state}()->for($owner, 'owner')->create()->delete();
        }

        $this->actingAs($owner)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 1)
                ->where('listings.0.id', $live->id)
            );
    }
}
