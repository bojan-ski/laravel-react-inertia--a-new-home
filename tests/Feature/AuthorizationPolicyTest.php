<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\User;
use App\Policies\AgencyPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function regularListing(string $state = 'approved'): Listing
    {
        return Listing::factory()
            ->for(User::factory()->regularUser(), 'owner')
            ->{$state}()
            ->create();
    }

    private function agencyListing(Agency $agency, string $state = 'approved'): Listing
    {
        return Listing::factory()
            ->{$state}()
            ->create(['user_id' => null, 'agency_id' => $agency->id]);
    }

    public function test_authenticated_user_can_view_a_publicly_visible_listing(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->assertTrue($user->can('view', $this->regularListing('approved')));
    }

    public function test_authenticated_user_cannot_view_a_non_public_listing(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->assertFalse($user->can('view', $this->regularListing('pendingReview')));
    }

    public function test_suspended_user_cannot_view_any_listing(): void
    {
        $user = User::factory()->suspended()->create();

        $this->assertFalse($user->can('view', $this->regularListing('approved')));
    }

    public function test_regular_user_can_update_own_pending_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->for($owner, 'owner')->pendingReview()->create();

        $this->assertTrue($owner->can('update', $listing));
    }

    public function test_regular_user_can_update_own_approved_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->for($owner, 'owner')->approved()->create();

        $this->assertTrue($owner->can('update', $listing));
    }

    public function test_regular_user_can_update_own_hidden_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->for($owner, 'owner')->hidden()->create();

        $this->assertTrue($owner->can('update', $listing));
    }

    public function test_regular_user_cannot_update_own_rejected_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->for($owner, 'owner')->rejected()->create();

        $this->assertFalse($owner->can('update', $listing));
    }

    public function test_regular_user_can_delete_own_listing_regardless_of_status(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->for($owner, 'owner')->rejected()->create();

        $this->assertTrue($owner->can('delete', $listing));
    }

    public function test_regular_user_cannot_update_another_users_listing(): void
    {
        $other = User::factory()->regularUser()->create();

        $this->assertFalse($other->can('update', $this->regularListing('approved')));
    }

    public function test_regular_user_cannot_delete_another_users_listing(): void
    {
        $other = User::factory()->regularUser()->create();

        $this->assertFalse($other->can('delete', $this->regularListing('approved')));
    }

    public function test_suspended_regular_user_cannot_update_own_listing(): void
    {
        $owner = User::factory()->suspended()->create();
        $listing = Listing::factory()->for($owner, 'owner')->approved()->create();

        $this->assertFalse($owner->can('update', $listing));
    }

    public function test_suspended_regular_user_cannot_delete_own_listing(): void
    {
        $owner = User::factory()->suspended()->create();
        $listing = Listing::factory()->for($owner, 'owner')->approved()->create();

        $this->assertFalse($owner->can('delete', $listing));
    }

    public function test_agency_user_can_update_agency_owned_listing_when_agency_is_approved(): void
    {
        $owner = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->for($owner)->create();

        $this->assertTrue($owner->can('update', $this->agencyListing($agency, 'approved')));
    }

    public function test_agency_user_cannot_update_agency_owned_listing_when_agency_is_pending(): void
    {
        $owner = User::factory()->agency()->create();
        $agency = Agency::factory()->pendingVerification()->for($owner)->create();

        $this->assertFalse($owner->can('update', $this->agencyListing($agency, 'approved')));
    }

    public function test_agency_user_cannot_update_agency_owned_listing_when_agency_is_rejected(): void
    {
        $owner = User::factory()->agency()->create();
        $agency = Agency::factory()->rejected()->for($owner)->create();

        $this->assertFalse($owner->can('update', $this->agencyListing($agency, 'approved')));
    }

    public function test_agency_user_cannot_update_agency_owned_listing_when_agency_is_hidden(): void
    {
        $owner = User::factory()->agency()->create();
        $agency = Agency::factory()->hidden()->for($owner)->create();

        $this->assertFalse($owner->can('update', $this->agencyListing($agency, 'approved')));
    }

    public function test_agency_user_cannot_update_a_regular_user_owned_listing(): void
    {
        $owner = User::factory()->agency()->create();
        Agency::factory()->approved()->for($owner)->create();

        $this->assertFalse($owner->can('update', $this->regularListing('approved')));
    }

    public function test_agency_user_cannot_delete_a_regular_user_owned_listing(): void
    {
        $owner = User::factory()->agency()->create();
        Agency::factory()->approved()->for($owner)->create();

        $this->assertFalse($owner->can('delete', $this->regularListing('approved')));
    }

    public function test_authenticated_user_can_bookmark_a_publicly_visible_listing(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->assertTrue($user->can('bookmark', $this->regularListing('approved')));
    }

    public function test_authenticated_user_cannot_bookmark_a_non_public_listing(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->assertFalse($user->can('bookmark', $this->regularListing('pendingReview')));
    }

    public function test_suspended_user_cannot_bookmark(): void
    {
        $user = User::factory()->suspended()->create();

        $this->assertFalse($user->can('bookmark', $this->regularListing('approved')));
    }

    public function test_admin_can_approve_a_pending_listing(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('approve', $this->regularListing('pendingReview')));
    }

    public function test_admin_can_reject_a_pending_listing(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('reject', $this->regularListing('pendingReview')));
    }

    public function test_admin_cannot_approve_an_approved_listing(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('approve', $this->regularListing('approved')));
    }

    public function test_admin_cannot_reject_an_approved_listing(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('reject', $this->regularListing('approved')));
    }

    public function test_admin_cannot_approve_a_rejected_listing(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('approve', $this->regularListing('rejected')));
    }

    public function test_admin_cannot_approve_a_hidden_listing(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('approve', $this->regularListing('hidden')));
    }

    public function test_agency_user_can_view_own_agency(): void
    {
        $owner = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->for($owner)->create();

        $this->assertTrue($owner->can('view', $agency));
    }

    public function test_agency_user_can_update_own_agency(): void
    {
        $owner = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->for($owner)->create();

        $this->assertTrue($owner->can('update', $agency));
    }

    public function test_agency_user_cannot_view_another_agency(): void
    {
        $owner = User::factory()->agency()->create();
        Agency::factory()->approved()->for($owner)->create();
        $otherAgency = Agency::factory()->approved()->create();

        $this->assertFalse($owner->can('view', $otherAgency));
    }

    public function test_agency_user_cannot_update_another_agency(): void
    {
        $owner = User::factory()->agency()->create();
        Agency::factory()->approved()->for($owner)->create();
        $otherAgency = Agency::factory()->approved()->create();

        $this->assertFalse($owner->can('update', $otherAgency));
    }

    public function test_suspended_agency_user_cannot_view_own_agency(): void
    {
        $owner = User::factory()->agency()->suspended()->create();
        $agency = Agency::factory()->approved()->for($owner)->create();

        $this->assertFalse($owner->can('view', $agency));
    }

    public function test_suspended_agency_user_cannot_update_own_agency(): void
    {
        $owner = User::factory()->agency()->suspended()->create();
        $agency = Agency::factory()->approved()->for($owner)->create();

        $this->assertFalse($owner->can('update', $agency));
    }

    public function test_admin_can_approve_a_pending_agency(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('approve', Agency::factory()->pendingVerification()->create()));
    }

    public function test_admin_can_reject_a_pending_agency(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('reject', Agency::factory()->pendingVerification()->create()));
    }

    public function test_admin_cannot_approve_an_already_approved_agency(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('approve', Agency::factory()->approved()->create()));
    }

    public function test_admin_cannot_approve_a_hidden_agency(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('approve', Agency::factory()->hidden()->create()));
    }

    public function test_view_public_is_true_for_an_approved_visible_agency(): void
    {
        $agency = Agency::factory()->approved()->create();

        $this->assertTrue((new AgencyPolicy)->viewPublic(null, $agency));
    }

    public function test_view_public_is_false_for_a_pending_agency(): void
    {
        $agency = Agency::factory()->pendingVerification()->create();

        $this->assertFalse((new AgencyPolicy)->viewPublic(null, $agency));
    }

    public function test_view_public_is_false_for_a_hidden_agency(): void
    {
        $agency = Agency::factory()->hidden()->create();

        $this->assertFalse((new AgencyPolicy)->viewPublic(null, $agency));
    }

    public function test_admin_can_view_any_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('viewAny', User::class));
    }

    public function test_regular_user_cannot_view_any_users(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->assertFalse($user->can('viewAny', User::class));
    }

    public function test_admin_can_suspend_an_active_non_admin_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $this->assertTrue($admin->can('suspend', $target));
    }

    public function test_admin_cannot_suspend_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('suspend', $admin));
    }

    public function test_admin_cannot_suspend_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $this->assertFalse($admin->can('suspend', $target));
    }

    public function test_admin_cannot_suspend_an_already_suspended_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->suspended()->create();

        $this->assertFalse($admin->can('suspend', $target));
    }

    public function test_admin_can_reactivate_a_suspended_non_admin_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->suspended()->create();

        $this->assertTrue($admin->can('reactivate', $target));
    }

    public function test_admin_cannot_reactivate_an_already_active_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $this->assertFalse($admin->can('reactivate', $target));
    }

    public function test_admin_can_delete_a_non_admin_non_self_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $this->assertTrue($admin->can('delete', $target));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('delete', $admin));
    }

    public function test_admin_cannot_delete_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $this->assertFalse($admin->can('delete', $target));
    }

    public function test_non_admin_cannot_suspend_a_user(): void
    {
        $actor = User::factory()->regularUser()->create();
        $target = User::factory()->regularUser()->create();

        $this->assertFalse($actor->can('suspend', $target));
    }

    public function test_only_admins_can_view_moderation_listings(): void
    {
        $this->assertTrue(User::factory()->admin()->create()->can('viewAny', Listing::class));
        $this->assertFalse(User::factory()->regularUser()->create()->can('viewAny', Listing::class));
        $this->assertFalse(User::factory()->agency()->create()->can('viewAny', Listing::class));
    }

    public function test_only_admins_can_view_moderation_agencies(): void
    {
        $this->assertTrue(User::factory()->admin()->create()->can('viewAny', Agency::class));
        $this->assertFalse(User::factory()->regularUser()->create()->can('viewAny', Agency::class));
        $this->assertFalse(User::factory()->agency()->create()->can('viewAny', Agency::class));
    }
}
