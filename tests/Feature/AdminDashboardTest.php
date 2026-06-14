<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_the_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_regular_user_gets_403(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_agency_user_gets_403(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_listing_counts_reflect_seeded_data(): void
    {
        $admin = User::factory()->admin()->create();

        Listing::factory()->count(3)->pendingReview()->create();
        Listing::factory()->count(5)->approved()->create();
        Listing::factory()->count(2)->rejected()->create();
        Listing::factory()->count(4)->hidden()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.totalListings', 14)
                ->where('stats.pendingListings', 3)
                ->where('stats.approvedListings', 5)
                ->where('stats.rejectedListings', 2)
            );
    }

    public function test_user_counts_reflect_seeded_data(): void
    {
        $admin = User::factory()->admin()->create();

        User::factory()->count(4)->regularUser()->create();
        User::factory()->count(2)->agency()->create();
        User::factory()->count(1)->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.totalRegularUsers', 4)
                ->where('stats.totalAgencyUsers', 2)
                ->where('stats.totalAdmins', 2)
            );
    }

    public function test_stats_prop_has_the_documented_keys(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->has('stats', fn (Assert $stats) => $stats
                    ->where('totalListings', 0)
                    ->where('pendingListings', 0)
                    ->where('approvedListings', 0)
                    ->where('rejectedListings', 0)
                    ->where('totalRegularUsers', 0)
                    ->where('totalAgencyUsers', 0)
                    ->where('totalAdmins', 1)
                    ->where('pendingAgencies', 0)
                    ->where('approvedAgencies', 0)
                    ->where('reportedMessages', 0)
                )
            );
    }
}
