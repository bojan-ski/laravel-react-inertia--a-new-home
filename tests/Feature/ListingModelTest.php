<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ListingModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_listing_has_default_status_and_currency(): void
    {
        $listing = new Listing;

        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertSame('EUR', $listing->currency);
    }

    public function test_listing_belongs_to_owner(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->recycle($user)->create();

        $this->assertInstanceOf(User::class, $listing->owner);
        $this->assertSame($user->id, $listing->owner->id);
    }

    public function test_user_has_many_listings_filtered_by_user_id(): void
    {
        $user = User::factory()->regularUser()->create();
        Listing::factory()->count(3)->recycle($user)->create();
        Listing::factory()->count(2)->create();

        $this->assertInstanceOf(HasMany::class, $user->listings());
        $this->assertCount(3, $user->listings);
        $this->assertSame('user_id', $user->listings()->getForeignKeyName());
    }

    public function test_publicly_visible_scope_returns_only_layer_one_visible(): void
    {
        $visible = Listing::factory()->approved()->create();
        $pending = Listing::factory()->pendingReview()->create();
        $future = Listing::factory()->approved()->create(['approved_at' => now()->addDay()]);
        $trashed = Listing::factory()->approved()->create();
        $trashed->delete();

        $ids = Listing::publiclyVisible()->pluck('id');

        $this->assertTrue($ids->contains($visible->id));
        $this->assertFalse($ids->contains($pending->id));
        $this->assertFalse($ids->contains($future->id));
        $this->assertFalse($ids->contains($trashed->id));
    }

    public function test_actually_publicly_visible_scope_excludes_suspended_owner_listings(): void
    {
        $activeOwner = User::factory()->regularUser()->create();
        $suspendedOwner = User::factory()->regularUser()->suspended()->create();

        $shown = Listing::factory()->recycle($activeOwner)->approved()->create();
        $hidden = Listing::factory()->recycle($suspendedOwner)->approved()->create();

        $ids = Listing::actuallyPubliclyVisible()->pluck('id');

        $this->assertTrue($ids->contains($shown->id));
        $this->assertFalse($ids->contains($hidden->id));
    }

    public function test_by_status_scope_filters_by_status(): void
    {
        $approved = Listing::factory()->approved()->create();
        $pending = Listing::factory()->pendingReview()->create();

        $ids = Listing::byStatus(ListingStatus::PendingReview)->pluck('id');

        $this->assertTrue($ids->contains($pending->id));
        $this->assertFalse($ids->contains($approved->id));
    }

    public function test_by_owner_scope_filters_by_owner(): void
    {
        $owner = User::factory()->regularUser()->create();
        $mine = Listing::factory()->recycle($owner)->create();
        $other = Listing::factory()->create();

        $ids = Listing::byOwner($owner)->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_status_predicate_methods(): void
    {
        $this->assertTrue(Listing::factory()->pendingReview()->make()->isPendingReview());
        $this->assertTrue(Listing::factory()->approved()->make()->isApproved());
        $this->assertTrue(Listing::factory()->rejected()->make()->isRejected());
        $this->assertTrue(Listing::factory()->hidden()->make()->isHidden());

        $approved = Listing::factory()->approved()->make();
        $this->assertFalse($approved->isPendingReview());
        $this->assertFalse($approved->isRejected());
        $this->assertFalse($approved->isHidden());
    }

    public function test_is_publicly_visible_instance_predicate(): void
    {
        $this->assertTrue(Listing::factory()->approved()->make()->isPubliclyVisible());
        $this->assertFalse(Listing::factory()->pendingReview()->make()->isPubliclyVisible());
        $this->assertFalse(
            Listing::factory()->approved()->make(['approved_at' => now()->addDay()])->isPubliclyVisible()
        );
    }

    public function test_should_be_hidden_due_to_owner_suspension(): void
    {
        $suspendedOwner = User::factory()->regularUser()->suspended()->create();
        $activeOwner = User::factory()->regularUser()->create();

        $hidden = Listing::factory()->recycle($suspendedOwner)->approved()->create();
        $shown = Listing::factory()->recycle($activeOwner)->approved()->create();

        $this->assertTrue($hidden->shouldBeHiddenDueToOwnerSuspension());
        $this->assertFalse($shown->shouldBeHiddenDueToOwnerSuspension());
    }

    public function test_is_actually_publicly_visible_respects_owner_suspension(): void
    {
        $suspendedOwner = User::factory()->regularUser()->suspended()->create();
        $listing = Listing::factory()->recycle($suspendedOwner)->approved()->create();

        $this->assertTrue($listing->isPubliclyVisible());
        $this->assertFalse($listing->isActuallyPubliclyVisible());
    }

    public function test_can_be_edited_by_owner(): void
    {
        $this->assertTrue(Listing::factory()->pendingReview()->make()->canBeEditedByOwner());
        $this->assertTrue(Listing::factory()->approved()->make()->canBeEditedByOwner());
        $this->assertTrue(Listing::factory()->hidden()->make()->canBeEditedByOwner());
        $this->assertFalse(Listing::factory()->rejected()->make()->canBeEditedByOwner());
    }

    public function test_can_be_moderated_only_when_pending(): void
    {
        $this->assertTrue(Listing::factory()->pendingReview()->make()->canBeModerated());
        $this->assertFalse(Listing::factory()->approved()->make()->canBeModerated());
        $this->assertFalse(Listing::factory()->rejected()->make()->canBeModerated());
        $this->assertFalse(Listing::factory()->hidden()->make()->canBeModerated());
    }

    public function test_should_return_to_pending_review_on_edit_only_when_approved(): void
    {
        $this->assertTrue(Listing::factory()->approved()->make()->shouldReturnToPendingReviewOnEdit());
        $this->assertFalse(Listing::factory()->pendingReview()->make()->shouldReturnToPendingReviewOnEdit());
        $this->assertFalse(Listing::factory()->hidden()->make()->shouldReturnToPendingReviewOnEdit());
        $this->assertFalse(Listing::factory()->rejected()->make()->shouldReturnToPendingReviewOnEdit());
    }

    public function test_factory_default_state_produces_approved_listing(): void
    {
        $listing = Listing::factory()->create();

        $this->assertSame(ListingStatus::Approved, $listing->status);
        $this->assertNotNull($listing->approved_at);
    }

    public function test_factory_pending_review_state(): void
    {
        $listing = Listing::factory()->pendingReview()->create();

        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
        $this->assertNull($listing->rejected_at);
        $this->assertNull($listing->rejection_reason);
    }

    public function test_factory_approved_state(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->assertSame(ListingStatus::Approved, $listing->status);
        $this->assertNotNull($listing->approved_at);
        $this->assertNull($listing->rejected_at);
    }

    public function test_factory_rejected_state(): void
    {
        $listing = Listing::factory()->rejected()->create();

        $this->assertSame(ListingStatus::Rejected, $listing->status);
        $this->assertNotNull($listing->rejected_at);
        $this->assertNotNull($listing->rejection_reason);
    }

    public function test_factory_hidden_state(): void
    {
        $listing = Listing::factory()->hidden()->create();

        $this->assertSame(ListingStatus::Hidden, $listing->status);
        $this->assertNull($listing->approved_at);
    }

    public function test_bulk_creation_does_not_collide_on_slug(): void
    {
        $listings = Listing::factory()->count(20)->create();

        $this->assertCount(20, $listings->pluck('slug')->unique());
    }

    public function test_casts_produce_typed_values(): void
    {
        $listing = Listing::factory()->approved()->create([
            'price' => 285000,
            'area_value' => 84,
        ]);

        $this->assertInstanceOf(ListingStatus::class, $listing->status);
        $this->assertSame('285000.00', $listing->price);
        $this->assertSame('84.00', $listing->area_value);
        $this->assertInstanceOf(Carbon::class, $listing->approved_at);
    }

    public function test_to_card_array_returns_documented_shape(): void
    {
        $listing = Listing::factory()->approved()->create();

        $card = $listing->toCardArray();

        $this->assertSame([
            'id', 'title', 'slug', 'listingType', 'propertyType', 'price',
            'currency', 'city', 'country', 'bedrooms', 'bathrooms',
            'areaValue', 'areaUnit', 'approvedAt', 'imageUrl', 'ownerName',
        ], array_keys($card));

        $this->assertSame($listing->id, $card['id']);
        $this->assertSame($listing->listing_type, $card['listingType']);
        $this->assertSame($listing->property_type, $card['propertyType']);
        $this->assertIsString($card['approvedAt']);
        $this->assertNull($card['imageUrl']);
    }

    public function test_to_card_array_approved_at_is_null_when_not_approved(): void
    {
        $card = Listing::factory()->pendingReview()->create()->toCardArray();

        $this->assertNull($card['approvedAt']);
    }

    public function test_model_uses_soft_deletes(): void
    {
        $listing = Listing::factory()->create();
        $listing->delete();

        $this->assertSoftDeleted($listing);
        $this->assertNull(Listing::find($listing->id));
        $this->assertNotNull(Listing::withTrashed()->find($listing->id));
    }
}
