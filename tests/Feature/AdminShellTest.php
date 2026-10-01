<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AgencyProfileChange;
use App\Models\ContactMessage;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingReport;
use App\Models\ReportedChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminShellTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_receives_no_admin_counts(): void
    {
        $this->get('/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('adminCounts', null));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'regular user' => ['regularUser'],
            'agency user' => ['agency'],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_receives_no_admin_counts(string $role): void
    {
        $this->actingAs(User::factory()->{$role}()->create())
            ->get('/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('adminCounts', null));
    }

    public function test_non_admin_on_an_admin_route_receives_no_admin_counts(): void
    {
        Route::middleware('web')
            ->get('/admin/_shell-probe', fn () => Inertia::render('Admin/Dashboard'))
            ->name('admin.shell-probe');

        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/admin/_shell-probe')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('adminCounts', null));
    }

    public function test_admin_on_a_public_page_receives_no_admin_counts(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('adminCounts', null));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function adminPages(): array
    {
        return [
            'dashboard' => ['/admin'],
            'agencies' => ['/admin/agencies'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_admin_pages_receive_the_pending_counts(string $url): void
    {
        $admin = User::factory()->admin()->create();

        Listing::factory()->count(2)->pendingReview()->create();
        Listing::factory()->approved()->create();
        Agency::factory()->pendingVerification()->create();
        AgencyProfileChange::factory()->create();
        ListingReport::factory()->create();
        ListingReport::factory()->resolved()->create();
        ReportedChatMessage::factory()->create();
        ReportedChatMessage::factory()->resolved()->create();
        ContactMessage::factory()->handled()->create();
        ContactMessage::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('adminCounts', [
                    'pendingListings' => 2,
                    'pendingAgencies' => 1,
                    'pendingAgencyChanges' => 1,
                    'reportedListings' => 1,
                    'reportedMessages' => 1,
                    'newContactMessages' => 2,
                ])
            );
    }

    public function test_admin_nav_carries_the_shell_labels(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->where('adminNav.view_site', __('app.admin.nav.view_site'))
                ->where('adminNav.aria_label', __('app.admin.nav.aria_label'))
                ->where('adminNav.pending_count', __('app.admin.nav.pending_count'))
            );
    }

    public function test_pending_queue_rows_carry_the_cover_image_url(): void
    {
        $admin = User::factory()->admin()->create();
        $withImages = Listing::factory()->pendingReview()->create(['created_at' => now()]);
        Listing::factory()->pendingReview()->create(['created_at' => now()->subDay()]);

        ListingImage::factory()->for($withImages)->create(['sort_order' => 1]);
        $cover = ListingImage::factory()->for($withImages)->create(['sort_order' => 0]);

        $this->actingAs($admin)
            ->get('/admin/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $withImages->id)
                ->where('listings.data.0.imageUrl', Storage::disk('public')->url($cover->small_path))
                ->where('listings.data.1.imageUrl', null)
            );
    }
}
