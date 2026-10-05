<?php

namespace Tests\Feature;

use App\Enums\AgencyStatus;
use App\Enums\UserStatus;
use App\Models\Agency;
use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use App\Notifications\UserSuspendedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_the_users_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Users/Index'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_regular_user_gets_403(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_agency_user_gets_403(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admins_are_excluded_from_the_list(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $regular = User::factory()->regularUser()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.id', $regular->id)
            );

        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_role_filter_works(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->regularUser()->create();
        User::factory()->count(3)->agency()->create();

        $this->actingAs($admin)
            ->get('/admin/users?role=agency')
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 3));
    }

    public function test_status_filter_works(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->regularUser()->create();
        User::factory()->count(3)->regularUser()->suspended()->create();

        $this->actingAs($admin)
            ->get('/admin/users?status=suspended')
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 3));
    }

    public function test_search_matches_name(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->regularUser()->create(['name' => 'Alice Anderson']);
        User::factory()->regularUser()->create(['name' => 'Bob Brown']);

        $this->actingAs($admin)
            ->get('/admin/users?search=alice')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Alice Anderson')
            );
    }

    public function test_search_matches_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->regularUser()->create(['email' => 'findme@example.com']);
        User::factory()->regularUser()->create(['email' => 'other@example.com']);

        $this->actingAs($admin)
            ->get('/admin/users?search=findme')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.email', 'findme@example.com')
            );
    }

    public function test_malformed_filters_are_rejected_instead_of_failing(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/users?role[]=agency&status[]=active&search[]=alice')
            ->assertSessionHasErrors(['role', 'status', 'search']);
    }

    public function test_unknown_role_and_status_filters_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/users?role=admin&status=banana')
            ->assertSessionHasErrors(['role', 'status']);
    }

    public function test_combined_filters_work(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->agency()->suspended()->create(['name' => 'Target Agency']);
        User::factory()->agency()->create(['name' => 'Active Agency']);
        User::factory()->regularUser()->suspended()->create(['name' => 'Target User']);

        $this->actingAs($admin)
            ->get('/admin/users?role=agency&status=suspended&search=target')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Target Agency')
            );
    }

    public function test_pagination_is_twenty_per_page(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(25)->regularUser()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 20)
                ->where('users.total', 25)
            );

        $this->actingAs($admin)
            ->get('/admin/users?page=2')
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 5));
    }

    public function test_admin_can_suspend_an_active_user(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/suspend")
            ->assertRedirect();

        $target->refresh();
        $this->assertSame(UserStatus::Suspended, $target->status);
        $this->assertNotNull($target->suspended_at);
    }

    public function test_admin_can_suspend_an_agency_user(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->agency()->create();

        $this->actingAs($admin)->post("/admin/users/{$target->id}/suspend");

        $this->assertTrue($target->refresh()->isSuspended());
    }

    public function test_admin_cannot_suspend_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$admin->id}/suspend")
            ->assertForbidden();

        $this->assertTrue($admin->refresh()->isActive());
    }

    public function test_admin_cannot_suspend_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$other->id}/suspend")
            ->assertForbidden();

        $this->assertTrue($other->refresh()->isActive());
    }

    public function test_admin_cannot_suspend_an_already_suspended_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->suspended()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/suspend")
            ->assertForbidden();
    }

    public function test_admin_can_reactivate_a_suspended_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->suspended()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/reactivate")
            ->assertRedirect();

        $target->refresh();
        $this->assertSame(UserStatus::Active, $target->status);
        $this->assertNull($target->suspended_at);
    }

    public function test_admin_cannot_reactivate_an_active_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/reactivate")
            ->assertForbidden();
    }

    public function test_reactivation_sends_no_email(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->suspended()->create();

        $this->actingAs($admin)->post("/admin/users/{$target->id}/reactivate");

        Notification::assertNothingSent();
    }

    public function test_admin_can_delete_a_regular_user_and_cascades(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create(['user_id' => $target->id]);
        Bookmark::factory()->create(['user_id' => $target->id, 'listing_id' => $listing->id]);

        $this->actingAs($admin)
            ->delete("/admin/users/{$target->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertDatabaseMissing('bookmarks', ['user_id' => $target->id]);
    }

    public function test_admin_can_delete_an_agency_user(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->agency()->create();

        $this->actingAs($admin)->delete("/admin/users/{$target->id}");

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete("/admin/users/{$admin->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_delete_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete("/admin/users/{$other->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_suspension_fires_the_suspension_email(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $this->actingAs($admin)->post("/admin/users/{$target->id}/suspend");

        Notification::assertSentTo($target, UserSuspendedNotification::class);
    }

    public function test_deletion_fires_the_account_deleted_email(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $this->actingAs($admin)->delete("/admin/users/{$target->id}");

        Notification::assertSentOnDemand(AccountDeletedNotification::class);
    }

    public function test_suspension_does_not_mutate_listing_status(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create(['user_id' => $target->id]);

        $this->actingAs($admin)->post("/admin/users/{$target->id}/suspend");

        $this->assertTrue($listing->refresh()->isApproved());
    }

    public function test_suspending_then_reactivating_keeps_listing_visible(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create(['user_id' => $target->id]);

        $this->actingAs($admin)->post("/admin/users/{$target->id}/suspend");
        $this->assertFalse(Listing::actuallyPubliclyVisible()->whereKey($listing->id)->exists());

        $this->actingAs($admin)->post("/admin/users/{$target->id}/reactivate");
        $this->assertTrue(Listing::actuallyPubliclyVisible()->whereKey($listing->id)->exists());
        $this->assertTrue($listing->refresh()->isApproved());
    }

    public function test_list_payload_uses_to_admin_list_array_shape(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->regularUser()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data.0', fn (Assert $row) => $row
                    ->hasAll(['id', 'name', 'email', 'role', 'status', 'suspendedAt', 'createdAt', 'agency'])
                    ->has('can', fn (Assert $can) => $can
                        ->where('canSuspend', true)
                        ->where('canReactivate', false)
                        ->where('canDelete', true)
                    )
                    ->etc()
                )
            );
    }

    public function test_agency_user_row_carries_their_agency(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->agency()->create(['name' => 'Agency Owner']);
        $agency = Agency::factory()->pendingVerification()->create([
            'user_id' => $owner->id,
            'name' => 'Adriatic Homes',
        ]);

        $this->actingAs($admin)
            ->get('/admin/users?search=Agency+Owner')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.agency', [
                    'id' => $agency->id,
                    'name' => 'Adriatic Homes',
                    'status' => 'pending_verification',
                ])
            );
    }

    public function test_users_without_an_agency_have_a_null_agency(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->regularUser()->create(['name' => 'Regular Person']);
        User::factory()->agency()->create(['name' => 'Agencyless Owner']);

        $this->actingAs($admin)
            ->get('/admin/users?search=Regular+Person')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.agency', null)
            );

        $this->actingAs($admin)
            ->get('/admin/users?search=Agencyless+Owner')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.agency', null)
            );
    }

    public function test_users_page_receives_agency_status_labels_and_table_copy(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page
                ->where('translations.agency_statuses', [
                    'pending_verification' => __('app.admin.users.agency_statuses.pending_verification'),
                    'approved' => __('app.admin.users.agency_statuses.approved'),
                    'rejected' => __('app.admin.users.agency_statuses.rejected'),
                    'hidden' => __('app.admin.users.agency_statuses.hidden'),
                ])
                ->where('translations.columns.user', __('app.admin.users.columns.user'))
                ->where('translations.columns.actions', __('app.admin.users.columns.actions'))
                ->where('translations.filters.search_label', __('app.admin.users.filters.search_label'))
                ->where('translations.filters.role_label', __('app.admin.users.filters.role_label'))
                ->where('translations.filters.status_label', __('app.admin.users.filters.status_label'))
                ->where('translations.count_one', __('app.admin.users.count_one'))
            );

        $this->assertSame(
            array_map(fn (AgencyStatus $status): string => $status->value, AgencyStatus::cases()),
            array_keys(__('app.admin.users.agency_statuses')),
        );
    }

    public function test_the_users_query_count_does_not_grow_with_accounts(): void
    {
        $admin = User::factory()->admin()->create();

        $countQueries = function () use ($admin): int {
            $actor = $admin->fresh();
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($actor)->get('/admin/users')->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $seed = function (): void {
            User::factory()->regularUser()->create();
            Agency::factory()->approved()->create();
        };

        $seed();
        $seed();
        $withTwo = $countQueries();

        foreach (range(1, 4) as $index) {
            $seed();
        }

        $this->assertSame(13, User::count());
        $this->assertSame($withTwo, $countQueries());
    }
}
