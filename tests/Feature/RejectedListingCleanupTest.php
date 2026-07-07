<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingCleanupService;
use App\Services\ListingImageService;
use App\Services\ListingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RejectedListingCleanupTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function trashedRejectedListing(?Carbon $rejectedAt = null): Listing
    {
        $listing = Listing::factory()->rejected()->create([
            'rejected_at' => $rejectedAt ?? now()->subDays(8),
        ]);

        $listing->delete();

        return $listing;
    }

    public function test_rejecting_a_listing_soft_deletes_it_and_stores_metadata(): void
    {
        $listing = Listing::factory()->pendingReview()->create();

        app(ListingService::class)->reject($listing, 'Incomplete information.');

        $listing->refresh();

        $this->assertSame(ListingStatus::Rejected, $listing->status);
        $this->assertNotNull($listing->rejected_at);
        $this->assertNull($listing->approved_at);
        $this->assertSame('Incomplete information.', $listing->rejection_reason);
        $this->assertTrue($listing->trashed());
    }

    public function test_rejected_listing_is_excluded_from_public_and_owner_queries(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($owner, 'owner')->create();

        app(ListingService::class)->reject($listing, 'Not allowed.');

        $this->assertFalse(Listing::actuallyPubliclyVisible()->whereKey($listing->id)->exists());
        $this->assertFalse($owner->listings()->whereKey($listing->id)->exists());
    }

    public function test_command_does_not_purge_a_listing_rejected_six_days_ago(): void
    {
        $listing = $this->trashedRejectedListing(now()->subDays(6));

        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertNotNull(Listing::withTrashed()->find($listing->id));
    }

    public function test_command_purges_a_listing_rejected_exactly_seven_days_ago(): void
    {
        $listing = $this->trashedRejectedListing(now()->subDays(7));

        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertNull(Listing::withTrashed()->find($listing->id));
    }

    public function test_command_purges_a_listing_rejected_thirty_days_ago(): void
    {
        $listing = $this->trashedRejectedListing(now()->subDays(30));

        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertNull(Listing::withTrashed()->find($listing->id));
    }

    public function test_command_does_not_purge_a_soft_deleted_non_rejected_listing(): void
    {
        $listing = Listing::factory()->approved()->create();
        $listing->delete();

        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertNotNull(Listing::withTrashed()->find($listing->id));
    }

    public function test_command_does_not_purge_a_rejected_listing_that_is_not_trashed(): void
    {
        $listing = Listing::factory()->rejected()->create([
            'rejected_at' => now()->subDays(30),
        ]);

        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertNotNull(Listing::find($listing->id));
    }

    public function test_command_does_not_purge_a_trashed_rejected_listing_with_null_rejected_at(): void
    {
        $listing = Listing::factory()->rejected()->create(['rejected_at' => null]);
        $listing->delete();

        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertNotNull(Listing::withTrashed()->find($listing->id));
    }

    public function test_purge_removes_image_files_from_storage(): void
    {
        Storage::fake('public');
        $listing = Listing::factory()->rejected()->create(['rejected_at' => now()->subDays(8)]);
        $image = app(ListingImageService::class)->upload($listing, UploadedFile::fake()->image('photo.jpg', 800, 600));
        $listing->delete();

        Storage::disk('public')->assertExists($image->small_path);
        Storage::disk('public')->assertExists($image->large_path);

        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->large_path);
    }

    public function test_purge_removes_listing_image_rows(): void
    {
        Storage::fake('public');
        $listing = Listing::factory()->rejected()->create(['rejected_at' => now()->subDays(8)]);
        $image = app(ListingImageService::class)->upload($listing, UploadedFile::fake()->image('photo.jpg', 800, 600));
        $listing->delete();

        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
    }

    public function test_purge_removes_bookmarks_via_the_foreign_key_cascade(): void
    {
        $listing = $this->trashedRejectedListing();
        $bookmarker = User::factory()->regularUser()->create();
        $bookmark = Bookmark::create(['user_id' => $bookmarker->id, 'listing_id' => $listing->id]);

        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
    }

    public function test_purge_hard_deletes_the_listing(): void
    {
        $listing = $this->trashedRejectedListing();

        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertNull(Listing::withTrashed()->find($listing->id));
    }

    public function test_command_reports_the_correct_count(): void
    {
        $this->trashedRejectedListing();
        $this->trashedRejectedListing();
        $this->trashedRejectedListing();

        $this->artisan('listings:purge-rejected')
            ->expectsOutput('Purged 3 rejected listings.')
            ->assertExitCode(0);
    }

    public function test_command_is_safe_on_an_empty_result_set(): void
    {
        $this->artisan('listings:purge-rejected')
            ->expectsOutput('Purged 0 rejected listings.')
            ->assertExitCode(0);
    }

    public function test_command_is_idempotent_across_repeated_runs(): void
    {
        $listing = $this->trashedRejectedListing();

        $this->artisan('listings:purge-rejected')->assertExitCode(0);
        $this->artisan('listings:purge-rejected')
            ->expectsOutput('Purged 0 rejected listings.')
            ->assertExitCode(0);

        $this->assertNull(Listing::withTrashed()->find($listing->id));
    }

    public function test_cleanup_service_is_a_noop_on_a_non_rejected_listing(): void
    {
        $listing = Listing::factory()->approved()->create();
        $listing->delete();

        app(ListingCleanupService::class)->forceDeleteRejectedListing($listing);

        $this->assertNotNull(Listing::withTrashed()->find($listing->id));
    }

    public function test_cleanup_service_is_a_noop_on_a_non_trashed_listing(): void
    {
        $listing = Listing::factory()->rejected()->create(['rejected_at' => now()->subDays(30)]);

        app(ListingCleanupService::class)->forceDeleteRejectedListing($listing);

        $this->assertNotNull(Listing::find($listing->id));
    }
}
