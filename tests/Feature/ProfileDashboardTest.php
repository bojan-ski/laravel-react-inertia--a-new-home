<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_regular_user_can_view_the_dashboard(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Profile/Index'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_agency_user_gets_403(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->get('/profile')
            ->assertForbidden();
    }

    public function test_admin_user_gets_403(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/profile')
            ->assertForbidden();
    }

    public function test_only_the_current_users_listings_are_shown(): void
    {
        $user = User::factory()->regularUser()->create();
        $other = User::factory()->regularUser()->create();

        $mine = Listing::factory()->approved()->for($user, 'owner')->create();
        Listing::factory()->approved()->for($other, 'owner')->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 1)
                ->where('listings.0.id', $mine->id)
            );
    }

    public function test_all_four_statuses_are_shown(): void
    {
        $user = User::factory()->regularUser()->create();

        Listing::factory()->pendingReview()->for($user, 'owner')->create();
        Listing::factory()->approved()->for($user, 'owner')->create();
        Listing::factory()->rejected()->for($user, 'owner')->create();
        Listing::factory()->hidden()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 4)
                ->where('listings', fn ($listings) => collect($listings)->pluck('status')->sort()->values()->all()
                    === ['approved', 'hidden', 'pending_review', 'rejected'])
            );
    }

    public function test_rejected_listing_includes_its_rejection_reason(): void
    {
        $user = User::factory()->regularUser()->create();

        Listing::factory()->rejected()->for($user, 'owner')->create([
            'rejection_reason' => 'Photos are too low quality.',
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.0.status', 'rejected')
                ->where('listings.0.rejectionReason', 'Photos are too low quality.')
            );
    }

    public function test_soft_deleted_listings_are_not_shown(): void
    {
        $user = User::factory()->regularUser()->create();

        $visible = Listing::factory()->approved()->for($user, 'owner')->create();
        $deleted = Listing::factory()->approved()->for($user, 'owner')->create();
        $deleted->delete();

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 1)
                ->where('listings.0.id', $visible->id)
            );
    }

    public function test_empty_state_when_user_has_no_listings(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->has('listings', 0));
    }

    public function test_listings_are_ordered_by_created_at_descending(): void
    {
        $user = User::factory()->regularUser()->create();

        $older = Listing::factory()->approved()->for($user, 'owner')->create([
            'created_at' => now()->subDays(2),
        ]);
        $newer = Listing::factory()->approved()->for($user, 'owner')->create([
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.0.id', $newer->id)
                ->where('listings.1.id', $older->id)
            );
    }
}
