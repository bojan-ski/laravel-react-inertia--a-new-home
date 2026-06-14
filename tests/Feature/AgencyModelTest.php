<?php

namespace Tests\Feature;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AgencyModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_agency_has_default_status(): void
    {
        $agency = new Agency;

        $this->assertSame(AgencyStatus::PendingVerification, $agency->status);
    }

    public function test_agency_belongs_to_user(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->recycle($user)->create();

        $this->assertInstanceOf(BelongsTo::class, $agency->user());
        $this->assertInstanceOf(User::class, $agency->user);
        $this->assertSame($user->id, $agency->user->id);
    }

    public function test_user_has_one_agency(): void
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->recycle($user)->create();

        $this->assertInstanceOf(HasOne::class, $user->agency());
        $this->assertInstanceOf(Agency::class, $user->agency);
        $this->assertSame($agency->id, $user->agency->id);
    }

    public function test_agency_has_many_listings(): void
    {
        $agency = Agency::factory()->create();
        Listing::factory()->count(3)->create(['user_id' => null, 'agency_id' => $agency->id]);

        $this->assertInstanceOf(HasMany::class, $agency->listings());
        $this->assertCount(3, $agency->listings);
        $this->assertSame('agency_id', $agency->listings()->getForeignKeyName());
    }

    public function test_listing_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $listing = Listing::factory()->create(['user_id' => null, 'agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $listing->agency);
        $this->assertSame($agency->id, $listing->agency->id);
    }

    public function test_publicly_visible_scope_excludes_pending_rejected_hidden(): void
    {
        $approved = Agency::factory()->approved()->create();
        $pending = Agency::factory()->pendingVerification()->create();
        $rejected = Agency::factory()->rejected()->create();
        $hidden = Agency::factory()->hidden()->create();
        $future = Agency::factory()->approved()->create(['approved_at' => now()->addDay()]);

        $ids = Agency::publiclyVisible()->pluck('id');

        $this->assertTrue($ids->contains($approved->id));
        $this->assertFalse($ids->contains($pending->id));
        $this->assertFalse($ids->contains($rejected->id));
        $this->assertFalse($ids->contains($hidden->id));
        $this->assertFalse($ids->contains($future->id));
    }

    public function test_actually_publicly_visible_scope_excludes_suspended_owner_agencies(): void
    {
        $activeOwner = User::factory()->agency()->create();
        $suspendedOwner = User::factory()->agency()->suspended()->create();

        $shown = Agency::factory()->recycle($activeOwner)->approved()->create();
        $hidden = Agency::factory()->recycle($suspendedOwner)->approved()->create();

        $ids = Agency::actuallyPubliclyVisible()->pluck('id');

        $this->assertTrue($ids->contains($shown->id));
        $this->assertFalse($ids->contains($hidden->id));
    }

    public function test_by_status_scope_filters_correctly(): void
    {
        $pending = Agency::factory()->pendingVerification()->create();
        $approved = Agency::factory()->approved()->create();

        $ids = Agency::byStatus(AgencyStatus::PendingVerification)->pluck('id');

        $this->assertTrue($ids->contains($pending->id));
        $this->assertFalse($ids->contains($approved->id));
    }

    public function test_status_predicates(): void
    {
        $this->assertTrue(Agency::factory()->pendingVerification()->create()->isPendingVerification());
        $this->assertTrue(Agency::factory()->approved()->create()->isApproved());
        $this->assertTrue(Agency::factory()->rejected()->create()->isRejected());
        $this->assertTrue(Agency::factory()->hidden()->create()->isHidden());
    }

    public function test_can_be_moderated_only_for_pending_verification(): void
    {
        $this->assertTrue(Agency::factory()->pendingVerification()->create()->canBeModerated());
        $this->assertFalse(Agency::factory()->approved()->create()->canBeModerated());
        $this->assertFalse(Agency::factory()->rejected()->create()->canBeModerated());
        $this->assertFalse(Agency::factory()->hidden()->create()->canBeModerated());
    }

    public function test_should_be_hidden_due_to_suspension(): void
    {
        $suspendedOwner = User::factory()->agency()->suspended()->create();
        $activeOwner = User::factory()->agency()->create();

        $suspended = Agency::factory()->recycle($suspendedOwner)->approved()->create();
        $active = Agency::factory()->recycle($activeOwner)->approved()->create();

        $this->assertTrue($suspended->shouldBeHiddenDueToSuspension());
        $this->assertFalse($active->shouldBeHiddenDueToSuspension());
    }

    public function test_is_actually_publicly_visible_false_for_suspended_owner(): void
    {
        $suspendedOwner = User::factory()->agency()->suspended()->create();
        $agency = Agency::factory()->recycle($suspendedOwner)->approved()->create();

        $this->assertTrue($agency->isPubliclyVisible());
        $this->assertFalse($agency->isActuallyPubliclyVisible());
    }

    public function test_is_publicly_visible_instance_check(): void
    {
        $this->assertTrue(Agency::factory()->approved()->create()->isPubliclyVisible());
        $this->assertFalse(Agency::factory()->pendingVerification()->create()->isPubliclyVisible());
        $this->assertFalse(Agency::factory()->approved()->create(['approved_at' => now()->addDay()])->isPubliclyVisible());
    }

    public function test_factory_states_produce_correct_status_and_timestamps(): void
    {
        $pending = Agency::factory()->pendingVerification()->create();
        $this->assertSame(AgencyStatus::PendingVerification, $pending->status);
        $this->assertNull($pending->approved_at);
        $this->assertNull($pending->rejection_reason);

        $approved = Agency::factory()->approved()->create();
        $this->assertSame(AgencyStatus::Approved, $approved->status);
        $this->assertNotNull($approved->approved_at);
        $this->assertNull($approved->rejection_reason);

        $rejected = Agency::factory()->rejected()->create();
        $this->assertSame(AgencyStatus::Rejected, $rejected->status);
        $this->assertNull($rejected->approved_at);
        $this->assertNotNull($rejected->rejection_reason);

        $hidden = Agency::factory()->hidden()->create();
        $this->assertSame(AgencyStatus::Hidden, $hidden->status);
        $this->assertNull($hidden->approved_at);
    }

    public function test_factory_default_creates_pending_agency_with_agency_user(): void
    {
        $agency = Agency::factory()->create();

        $this->assertSame(AgencyStatus::PendingVerification, $agency->status);
        $this->assertTrue($agency->user->isAgency());
    }

    public function test_bulk_creation_does_not_collide_on_slug(): void
    {
        $agencies = Agency::factory()->count(10)->create();

        $this->assertCount(10, $agencies->pluck('slug')->unique());
    }

    public function test_to_moderation_array_shape(): void
    {
        $owner = User::factory()->agency()->create(['name' => 'Owner Name', 'email' => 'owner@example.test']);
        $agency = Agency::factory()->recycle($owner)->create();

        $array = $agency->toModerationArray();

        $this->assertSame([
            'id',
            'name',
            'slug',
            'email',
            'phone',
            'website',
            'address',
            'description',
            'ownerName',
            'ownerEmail',
            'createdAt',
        ], array_keys($array));

        $this->assertSame('Owner Name', $array['ownerName']);
        $this->assertSame('owner@example.test', $array['ownerEmail']);
        $this->assertSame($agency->created_at->toIso8601String(), $array['createdAt']);
    }

    public function test_unique_constraint_prevents_duplicate_agency_per_user(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->recycle($user)->create();

        $this->expectException(QueryException::class);

        Agency::factory()->recycle($user)->create();
    }

    public function test_hard_deleting_agency_cascades_to_listings_and_bookmarks(): void
    {
        $agency = Agency::factory()->create();
        $listing = Listing::factory()->approved()->create(['user_id' => null, 'agency_id' => $agency->id]);
        $bookmark = Bookmark::factory()->create(['listing_id' => $listing->id]);

        $agency->delete();

        $this->assertDatabaseMissing('agencies', ['id' => $agency->id]);
        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
    }
}
