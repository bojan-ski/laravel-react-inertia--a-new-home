<?php

namespace Tests\Feature;

use App\Enums\ListingReportResolution;
use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Bookmark;
use App\Models\ContactMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingReport;
use App\Models\ReportedChatMessage;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\AdminStatsService;
use App\Services\ConversationService;
use App\Services\ListingService;
use App\Services\SavedSearchService;
use Database\Seeders\ReviewScenarioSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ReviewScenarioSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_seeds_every_account_and_keyed_record_of_the_walkthrough(): void
    {
        Storage::fake('public');
        $this->freezeSecond();

        $this->seed(ReviewScenarioSeeder::class);

        $this->assertAccounts();
        $this->assertTotals();
        $this->assertSellerListings();
        $this->assertAgencyListings();
        $this->assertBrowseData();
        $this->assertBuyerData();
        $this->assertReportsAndTheRest();
    }

    public function test_it_refuses_to_run_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        try {
            $this->app->make(ReviewScenarioSeeder::class)->run();
            $this->fail('The seeder ran in production.');
        } catch (RuntimeException $exception) {
            $this->assertSame('ReviewScenarioSeeder only runs in the local and testing environments.', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('listings', 0);
    }

    private function assertAccounts(): void
    {
        $accounts = [
            'admin@newbeginning.test' => ['admin', 'active'],
            'seller@newbeginning.test' => ['regular_user', 'active'],
            'buyer@newbeginning.test' => ['regular_user', 'active'],
            'empty@newbeginning.test' => ['regular_user', 'active'],
            'collector@newbeginning.test' => ['regular_user', 'active'],
            'reset@newbeginning.test' => ['regular_user', 'active'],
            'offender@newbeginning.test' => ['regular_user', 'active'],
            'cascade@newbeginning.test' => ['regular_user', 'active'],
            'suspended@newbeginning.test' => ['regular_user', 'suspended'],
            'leaver@newbeginning.test' => ['regular_user', 'active'],
            'removed@newbeginning.test' => ['regular_user', 'active'],
            'agency@newbeginning.test' => ['agency', 'active'],
            'agency-pending@newbeginning.test' => ['agency', 'active'],
            'agency-rejectme@newbeginning.test' => ['agency', 'active'],
            'agency-change@newbeginning.test' => ['agency', 'active'],
            'agency-leaver@newbeginning.test' => ['agency', 'active'],
            'agency-suspended@newbeginning.test' => ['agency', 'suspended'],
        ];

        foreach ($accounts as $email => [$role, $status]) {
            $user = User::where('email', $email)->sole();

            $this->assertSame($role, $user->role->value, $email);
            $this->assertSame($status, $user->status->value, $email);
            $this->assertTrue(Hash::check('password', $user->password), $email);
        }

        $this->assertDatabaseMissing('users', ['email' => 'gone@newbeginning.test']);
        $this->assertSame(['admin@newbeginning.test'], User::where('role', UserRole::Admin)->pluck('email')->all());

        $empty = User::where('email', 'empty@newbeginning.test')->sole();
        $this->assertSame([0, 0, 0, 0], [
            $empty->listings()->count(),
            $empty->bookmarks()->count(),
            $empty->savedSearches()->count(),
            Conversation::where('buyer_id', $empty->id)->orWhere('owner_id', $empty->id)->count(),
        ]);
    }

    private function assertTotals(): void
    {
        $this->assertSame(59, ReviewScenarioSeeder::APPROVED_LISTINGS);
        $this->assertSame(55, ReviewScenarioSeeder::PUBLIC_LISTINGS);
        $this->assertSame(4, ReviewScenarioSeeder::PENDING_LISTINGS);
        $this->assertSame(2, ReviewScenarioSeeder::REJECTED_LISTINGS);
        $this->assertSame(2, ReviewScenarioSeeder::CLOSED_LISTINGS);
        $this->assertSame(15, ReviewScenarioSeeder::REGULAR_USERS);
        $this->assertSame(19, ReviewScenarioSeeder::AGENCY_USERS);
        $this->assertSame(16, ReviewScenarioSeeder::APPROVED_AGENCIES);
        $this->assertSame(15, ReviewScenarioSeeder::DIRECTORY_AGENCIES);

        $this->assertSame([
            'totalListings' => 67,
            'pendingListings' => 4,
            'approvedListings' => 59,
            'rejectedListings' => 2,
            'closedListings' => 2,
            'totalRegularUsers' => 15,
            'totalAgencyUsers' => 19,
            'totalAdmins' => 1,
            'pendingAgencies' => 2,
            'approvedAgencies' => 16,
            'pendingAgencyChanges' => 1,
            'reportedListings' => 3,
            'reportedMessages' => 1,
            'newContactMessages' => 3,
        ], app(AdminStatsService::class)->dashboardStats());

        $this->assertSame(55, Listing::actuallyPubliclyVisible()->count());
        $this->assertSame(0, Listing::withTrashed()->where('status', ListingStatus::Hidden)->count());
        $this->assertSame(2, Listing::expired()->count());
        $this->assertSame(2, Listing::onlyTrashed()->count());
        $this->assertSame(
            ['G-EXPIRING Seafront Apartment in Piran', 'S-EXPIRING Park Apartment in Vienna'],
            Listing::dueForExpiryReminder()->orderBy('title')->pluck('title')->all(),
        );
    }

    private function assertSellerListings(): void
    {
        $seller = User::where('email', 'seller@newbeginning.test')->sole();
        $sellerKeys = [
            'S-SALE', 'S-EDIT', 'S-DEL', 'S-FRESH', 'S-EXPIRING', 'S-EXPIRED', 'S-EXPIRED-B', 'S-SOLD', 'S-RENTED', 'S-ANCHOR',
            'S-PEND-REJECT', 'S-PEND-DIGEST', 'S-PEND-PHONE-A', 'S-PEND-PHONE-R', 'S-REJ-2D', 'S-REJ-8D', 'S-REPORT-DOWN', 'S-REPORT-KEEP',
        ];

        foreach ($sellerKeys as $key) {
            $this->assertSame($seller->id, $this->keyed($key)->user_id, $key);
        }

        foreach (['S-SALE', 'S-EDIT', 'S-DEL', 'S-FRESH', 'S-EXPIRING', 'S-ANCHOR', 'S-REPORT-DOWN', 'S-REPORT-KEEP'] as $key) {
            $this->assertTrue($this->keyed($key)->isActuallyPubliclyVisible(), $key);
        }

        $this->assertSame('sale', $this->keyed('S-SALE')->listing_type);

        $fresh = $this->keyed('S-FRESH');
        $this->assertTrue($fresh->approved_at->equalTo(now()->subDays(30)));
        $this->assertFalse($fresh->canBeRenewedByOwner());

        $expiring = $this->keyed('S-EXPIRING');
        $this->assertTrue($expiring->expires_at->equalTo(now()->addDays(5)));
        $this->assertTrue($expiring->canBeRenewedByOwner());

        foreach (['S-EXPIRED', 'S-EXPIRED-B'] as $key) {
            $this->assertTrue($this->keyed($key)->isExpired(), $key);
            $this->assertTrue($this->keyed($key)->canBeRenewedByOwner(), $key);
        }

        $this->assertSame(ListingStatus::Sold, $this->keyed('S-SOLD')->status);
        $this->assertNotNull($this->keyed('S-SOLD')->closed_at);
        $this->assertSame(ListingStatus::Rented, $this->keyed('S-RENTED')->status);
        $this->assertNotNull($this->keyed('S-RENTED')->closed_at);

        foreach (['S-PEND-REJECT', 'S-PEND-DIGEST', 'S-PEND-PHONE-A', 'S-PEND-PHONE-R'] as $key) {
            $this->assertSame(ListingStatus::PendingReview, $this->keyed($key)->status, $key);
        }

        foreach (['S-REJ-2D' => 2, 'S-REJ-8D' => 8] as $key => $daysAgo) {
            $rejected = $this->keyed($key);
            $this->assertSame(ListingStatus::Rejected, $rejected->status, $key);
            $this->assertTrue($rejected->trashed(), $key);
            $this->assertTrue($rejected->rejected_at->equalTo(now()->subDays($daysAgo)), $key);
            $this->assertNotNull($rejected->rejection_reason, $key);
        }

        $this->assertSame(2, $this->keyed('S-REJ-8D')->images()->count());

        $anchor = $this->keyed('S-ANCHOR');
        $this->assertSame(['sale', 'house', 'Ljubljana', '400000.00'], [$anchor->listing_type, $anchor->property_type, $anchor->city, $anchor->price]);
        $this->assertSame(6, $anchor->images()->count());
        $this->assertNull($anchor->contact_name);
        $this->assertNull($anchor->contact_email);
        $this->assertNull($anchor->contact_phone);

        $neighbours = Listing::actuallyPubliclyVisible()
            ->whereKeyNot($anchor->id)
            ->where('listing_type', 'sale')
            ->where('property_type', 'house')
            ->whereBetween('price', [280_000, 520_000]);
        $this->assertSame(4, (clone $neighbours)->count());
        $this->assertSame(2, (clone $neighbours)->where('city', 'Ljubljana')->count());

        $this->assertSame([2, 1, 1], [
            $this->keyed('S-REPORT-DOWN')->openReports()->distinct('reported_by_user_id')->count('reported_by_user_id'),
            $this->keyed('S-REPORT-KEEP')->openReports()->count(),
            $this->keyed('S-EXPIRED-B')->openReports()->count(),
        ]);
    }

    private function assertAgencyListings(): void
    {
        $agency = Agency::where('slug', 'harbour-hill-estates')->sole();
        $this->assertSame('agency@newbeginning.test', $agency->user->email);
        $this->assertTrue($agency->isApproved());

        foreach (['G-RENT', 'G-EXPIRING', 'G-EDIT', 'G-DEL', 'G-CHAT'] as $key) {
            $listing = $this->keyed($key);
            $this->assertSame($agency->id, $listing->agency_id, $key);
            $this->assertNull($listing->user_id, $key);
            $this->assertTrue($listing->isActuallyPubliclyVisible(), $key);
        }

        $this->assertSame('rent', $this->keyed('G-RENT')->listing_type);
        $this->assertTrue($this->keyed('G-EXPIRING')->expires_at->equalTo(now()->addDays(5)));
        $this->assertSame(2, $this->keyed('G-EDIT')->images()->count());
    }

    private function assertBrowseData(): void
    {
        $this->assertGreaterThan(12, Listing::actuallyPubliclyVisible()->count());
        $this->assertSame(['rent', 'sale'], Listing::actuallyPubliclyVisible()->distinct()->orderBy('listing_type')->pluck('listing_type')->all());
        $this->assertGreaterThanOrEqual(8, Listing::actuallyPubliclyVisible()->distinct()->count('property_type'));
        $this->assertGreaterThanOrEqual(6, Listing::actuallyPubliclyVisible()->distinct()->count('country'));
        $this->assertGreaterThanOrEqual(10, Listing::actuallyPubliclyVisible()->distinct()->count('city'));
        $this->assertSame(Listing::actuallyPubliclyVisible()->count(), Listing::actuallyPubliclyVisible()->distinct()->count('price'));
        $this->assertSame(Listing::actuallyPubliclyVisible()->count(), Listing::actuallyPubliclyVisible()->distinct()->count('approved_at'));
        $this->assertGreaterThan(0, Listing::actuallyPubliclyVisible()->whereJsonLength('amenities', 0)->count());
        $this->assertGreaterThan(0, Listing::actuallyPubliclyVisible()->whereJsonContains('amenities', 'parking')->count());

        $imageCounts = Listing::withTrashed()
            ->where('status', ListingStatus::Approved)
            ->withCount('images')
            ->pluck('images_count', 'title');
        $this->assertSame([ReviewScenarioSeeder::NO_PHOTO_TITLE], $imageCounts->filter(fn (int $count): bool => $count === 0)->keys()->all());
        $this->assertSame(['S-ANCHOR Family House in Ljubljana'], $imageCounts->filter(fn (int $count): bool => $count > 3)->keys()->all());

        ListingImage::all()->each(function (ListingImage $image): void {
            Storage::disk('public')->assertExists([$image->small_path, $image->large_path]);
        });

        $directory = Agency::actuallyPubliclyVisible()->withCount(['listings' => fn ($query) => $query->actuallyPubliclyVisible()])->get();
        $this->assertSame(15, $directory->count());
        $this->assertSame(0, $directory->where('listings_count', 0)->count());
        $this->assertSame([13], $directory->where('listings_count', '>', 12)->pluck('listings_count')->all());
        $this->assertSame(16, Agency::where('status', 'approved')->count());
        $this->assertSame(['agency-hidden@newbeginning.test'], Agency::where('status', 'hidden')->with('user')->get()->pluck('user.email')->all());
        $this->assertSame(
            ['agency-pending@newbeginning.test', 'agency-rejectme@newbeginning.test'],
            Agency::where('status', 'pending_verification')->with('user')->get()->pluck('user.email')->sort()->values()->all(),
        );

        $suspendedAgency = Agency::whereRelation('user', 'email', 'agency-suspended@newbeginning.test')->sole();
        $this->assertTrue($suspendedAgency->isApproved());
        $this->assertSame(2, $suspendedAgency->listings()->where('status', ListingStatus::Approved)->count());
        $this->assertSame(0, $suspendedAgency->listings()->actuallyPubliclyVisible()->count());

        $this->assertGreaterThan(20, User::whereNot('role', UserRole::Admin)->count());
    }

    private function assertBuyerData(): void
    {
        $buyer = User::where('email', 'buyer@newbeginning.test')->sole();
        $cascade = User::where('email', 'cascade@newbeginning.test')->sole();

        $bookmarked = Bookmark::where('user_id', $buyer->id)->with('listing')->get()->pluck('listing');
        $this->assertSame(4, $bookmarked->count());
        $this->assertEqualsCanonicalizing(
            [$this->keyed('S-ANCHOR')->id, $this->keyed('S-SOLD')->id, $this->keyed('S-EXPIRED-B')->id],
            $bookmarked->where('user_id', '!=', $cascade->id)->pluck('id')->all(),
        );
        $this->assertSame(1, $bookmarked->where('user_id', $cascade->id)->count());

        $ownListings = Listing::where('user_id', $buyer->id)->get();
        $this->assertSame(1, $ownListings->count());
        $this->assertTrue($ownListings->first()->isActuallyPubliclyVisible());

        $asBuyer = Conversation::where('buyer_id', $buyer->id)->get();
        $this->assertSame(3, $asBuyer->count());
        $this->assertSame(1, $asBuyer->where('listing_id', $this->keyed('S-FRESH')->id)->count());
        $this->assertSame(1, $asBuyer->whereNull('owner_id')->whereNull('listing_id')->count());
        $this->assertSame(1, $asBuyer->where('owner_id', User::where('email', 'leaver@newbeginning.test')->value('id'))->count());

        $offenderThread = Conversation::where('owner_id', $buyer->id)->sole();
        $this->assertSame(User::where('email', 'offender@newbeginning.test')->value('id'), $offenderThread->buyer_id);
        $this->assertSame($ownListings->first()->id, $offenderThread->listing_id);

        $chat = app(ConversationService::class);
        $this->assertSame(2, $chat->unreadCountFor($buyer));
        $this->assertSame(0, $chat->unreadCountFor(User::where('email', 'seller@newbeginning.test')->sole()));

        $searches = SavedSearch::where('user_id', $buyer->id)->get();
        $this->assertSame(2, $searches->count());
        $alertSearch = $searches->where('alerts_enabled', true)->sole();
        $this->assertNotNull($alertSearch->last_alerted_at);

        $matches = app(ListingService::class)
            ->applyPublicFilters(Listing::withTrashed(), app(SavedSearchService::class)->resolveFilters($alertSearch))
            ->pluck('title')
            ->all();
        $this->assertSame(['S-PEND-DIGEST Stone Cottage in Bled'], $matches);

        $collector = User::where('email', 'collector@newbeginning.test')->sole();
        $this->assertSame(10, SavedSearch::where('user_id', $collector->id)->count());
        $this->assertSame(0, SavedSearch::where('user_id', $collector->id)->where('alerts_enabled', true)->count());
        $this->assertSame(1, SavedSearch::where('alerts_enabled', true)->count());
    }

    private function assertReportsAndTheRest(): void
    {
        $openMessageReport = ReportedChatMessage::whereNull('resolved_at')->sole();
        $this->assertSame('noisy-buyer@newbeginning.test', $openMessageReport->offendingUser->email);
        $this->assertSame('Nobody would buy this dump anyway.', $openMessageReport->message->body);
        $this->assertSame(0, ReportedChatMessage::whereNotNull('resolved_at')->count());

        $resolved = ListingReport::whereNotNull('resolved_at')->with('listing')->sole();
        $this->assertSame(ListingReportResolution::Dismissed, $resolved->resolution);
        $this->assertSame('admin@newbeginning.test', $resolved->resolvedBy->email);
        $this->assertDoesNotMatchRegularExpression('/^[SG]-/', $resolved->listing->title);
        $this->assertSame(4, ListingReport::whereNull('resolved_at')->count());

        $change = Agency::whereRelation('user', 'email', 'agency-change@newbeginning.test')->with('pendingChange')->sole();
        $this->assertTrue($change->isApproved());
        $proposed = array_keys($change->pendingChange->proposed_values);
        sort($proposed);
        $this->assertSame(['description', 'phone'], $proposed);

        $cascade = User::where('email', 'cascade@newbeginning.test')->sole();
        $this->assertSame(3, Listing::where('user_id', $cascade->id)->actuallyPubliclyVisible()->count());

        $leaverListing = Listing::whereRelation('owner', 'email', 'leaver@newbeginning.test')->sole();
        $this->assertTrue($leaverListing->isActuallyPubliclyVisible());
        $this->assertGreaterThan(0, $leaverListing->images()->count());

        $agencyLeaverListing = Listing::whereRelation('agency.user', 'email', 'agency-leaver@newbeginning.test')->sole();
        $this->assertTrue($agencyLeaverListing->isActuallyPubliclyVisible());
        $this->assertGreaterThan(0, $agencyLeaverListing->images()->count());

        $this->assertSame(3, ContactMessage::whereNull('handled_at')->count());
        $this->assertSame(2, ContactMessage::whereNotNull('handled_at')->count());
    }

    private function keyed(string $key): Listing
    {
        return Listing::withTrashed()->where('title', 'like', "{$key} %")->sole();
    }
}
