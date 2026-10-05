<?php

namespace Tests\Feature\System;

use App\Enums\AgencyStatus;
use App\Enums\ListingReportReason;
use App\Enums\ListingStatus;
use App\Enums\UserStatus;
use App\Events\ChatMessageSent;
use App\Models\Agency;
use App\Models\AgencyProfileChange;
use App\Models\Bookmark;
use App\Models\ChatMessage;
use App\Models\ContactMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingReport;
use App\Models\ReportedChatMessage;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use App\Notifications\AgencyApprovedNotification;
use App\Notifications\AgencyChangesApprovedNotification;
use App\Notifications\AgencyRejectedNotification;
use App\Notifications\ListingApprovedNotification;
use App\Notifications\ListingRejectedNotification;
use App\Notifications\ListingTakenDownNotification;
use App\Notifications\SavedSearchDigestNotification;
use App\Notifications\UserSuspendedNotification;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MarketplaceIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function listingPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Modern City Apartment',
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => 285000,
            'currency' => 'EUR',
            'description' => 'A bright two-bedroom apartment in the city centre.',
            'country' => 'Serbia',
            'city' => 'Belgrade',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function agencyRegisterPayload(string $email, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Agency Owner',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'agency',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function agencySetupPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Premier Properties',
            'legal_name' => 'Premier Properties LLC',
            'vat_id' => 'DE123456789',
            'registration_number' => '01234567',
            'registration_country' => 'DE',
            'email' => 'contact@premier.test',
            'phone' => '+1 555 0100',
            'website' => 'https://premier.test',
            'address' => '12 Market Street, Belgrade',
            'description' => 'A boutique agency specialising in city apartments.',
        ], $overrides);
    }

    /**
     * @return array<int, int>
     */
    private function publicListingIds(): array
    {
        $ids = [];

        $this->get('/listings')
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$ids) {
                $ids = array_column($page->toArray()['props']['listings']['data'], 'id');
            });

        return $ids;
    }

    /**
     * @return array<int, int>
     */
    private function bookmarkedListingIds(User $user): array
    {
        $ids = [];

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$ids) {
                $ids = array_column($page->toArray()['props']['listings'], 'id');
            });

        return $ids;
    }

    /**
     * @return array<int, int>
     */
    private function directoryAgencyIds(): array
    {
        $ids = [];

        $this->get('/agencies')
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$ids) {
                $ids = array_column($page->toArray()['props']['agencies']['data'], 'id');
            });

        return $ids;
    }

    /**
     * @return array<int, int>
     */
    private function unavailableBookmarkIds(User $user): array
    {
        $ids = [];

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$ids) {
                $ids = array_column($page->toArray()['props']['closedListings'], 'id');
            });

        return $ids;
    }

    /**
     * @return array<int, int>|null
     */
    private function agencyPageListingIds(Agency $agency): ?array
    {
        $response = $this->get("/agencies/{$agency->slug}");

        if ($response->getStatusCode() === 404) {
            return null;
        }

        return array_column($this->props($response)['listings']['data'], 'id');
    }

    /**
     * @return array<int, int>
     */
    private function similarListingIds(User $viewer, Listing $anchor): array
    {
        $ids = null;

        $this->actingAs($viewer)
            ->get("/listings/{$anchor->slug}")
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$ids) {
                $page->loadDeferredProps(function (Assert $reload) use (&$ids) {
                    $ids = array_column($reload->toArray()['props']['similarListings'], 'id');
                });
            });

        $this->assertNotNull($ids, 'The similar listings were not loaded.');

        return $ids;
    }

    /**
     * @return array<int, int>
     */
    private function reportQueueListingIds(User $admin): array
    {
        return array_column($this->props($this->actingAs($admin)->get('/admin/listing-reports'))['listings']['data'], 'id');
    }

    /**
     * @return array<int, list<string>>
     */
    private function digestTitles(User ...$users): array
    {
        $before = collect($users)->mapWithKeys(fn (User $user): array => [
            $user->id => Notification::sent($user, SavedSearchDigestNotification::class)->count(),
        ]);

        $this->artisan('saved-searches:send-digest')->assertSuccessful();

        return collect($users)->mapWithKeys(function (User $user) use ($before): array {
            $sent = Notification::sent($user, SavedSearchDigestNotification::class);

            if ($sent->count() === $before[$user->id]) {
                return [$user->id => []];
            }

            return [$user->id => collect($sent->last()->sections)
                ->flatMap(fn (array $section): array => array_column($section['listings'], 'title'))
                ->all()];
        })->all();
    }

    private function sitemapListsAgency(Agency $agency): bool
    {
        return str_contains((string) $this->get('/sitemap.xml')->assertOk()->getContent(), "/agencies/{$agency->slug}<");
    }

    private function unreadCount(User $user): int
    {
        return $this->props($this->actingAs($user)->get('/listings'))['unreadMessages'];
    }

    /**
     * @return array<string, mixed>
     */
    private function props(TestResponse $response): array
    {
        $props = [];

        $response->assertOk()->assertInertia(function (Assert $page) use (&$props) {
            $props = $page->toArray()['props'];
        });

        return $props;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: User, 1: Agency}
     */
    private function approvedAgency(array $overrides = []): array
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id, ...$overrides]);

        return [$user, $agency];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function liveListing(array $overrides = []): Listing
    {
        return Listing::factory()->approved()->create([
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => 300000,
            'country' => 'Serbia',
            'city' => 'Belgrade',
            'approved_at' => now(),
            'expires_at' => now()->addDays(60),
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function agencyListing(Agency $agency, array $overrides = []): Listing
    {
        return $this->liveListing(['user_id' => null, 'agency_id' => $agency->id, ...$overrides]);
    }

    private function savedSearch(User $user, ?CarbonInterface $lastAlertedAt = null): SavedSearch
    {
        return SavedSearch::factory()->for($user)
            ->withFilters(['listingType' => 'sale', 'city' => 'Belgrade'])
            ->create(['last_alerted_at' => $lastAlertedAt ?? now()]);
    }

    // ---- Test 1 — full listing lifecycle --------------------------------

    public function test_full_listing_lifecycle_from_creation_to_purge(): void
    {
        Notification::fake();
        Storage::fake('public');

        $owner = User::factory()->regularUser()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($owner)
            ->post('/my-listings', $this->listingPayload())
            ->assertRedirect('/profile');

        $listing = Listing::firstOrFail();
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNotContains($listing->id, $this->publicListingIds());

        $this->actingAs($owner)->post("/my-listings/{$listing->id}/images", [
            'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ]);
        $image = $listing->images()->firstOrFail();
        Storage::disk('public')->assertExists($image->small_path);

        $this->actingAs($admin)
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertRedirect();
        Notification::assertSentTo($owner, ListingApprovedNotification::class);

        $listing->refresh();
        $this->assertSame(ListingStatus::Approved, $listing->status);
        $this->assertContains($listing->id, $this->publicListingIds());

        $bookmarker = User::factory()->regularUser()->create();
        $this->actingAs($bookmarker)->post("/listings/{$listing->id}/bookmark");
        $bookmark = Bookmark::firstOrFail();

        $this->actingAs($owner)
            ->put("/my-listings/{$listing->id}", $this->listingPayload(['title' => 'Edited Title']))
            ->assertRedirect('/profile');

        $listing->refresh();
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
        $this->assertNotContains($listing->id, $this->publicListingIds());

        $this->actingAs($admin)
            ->post("/admin/listings/{$listing->id}/reject", ['reason' => 'Does not meet the guidelines.'])
            ->assertRedirect();
        Notification::assertSentTo($owner, ListingRejectedNotification::class);

        $listing = Listing::withTrashed()->findOrFail($listing->id);
        $this->assertSame(ListingStatus::Rejected, $listing->status);
        $this->assertSame('Does not meet the guidelines.', $listing->rejection_reason);
        $this->assertTrue($listing->trashed());

        $this->actingAs($owner)
            ->get("/my-listings/{$listing->id}/edit")
            ->assertNotFound();

        $this->travel(8)->days();
        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertNull(Listing::withTrashed()->find($listing->id));
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->large_path);
        $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
    }

    // ---- Test 2 — bookmark visibility under suspension ------------------

    public function test_bookmark_visibility_survives_suspension_without_mutating_state(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $bookmarker = User::factory()->regularUser()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($bookmarker)->post("/listings/{$listing->id}/bookmark");
        $bookmark = Bookmark::firstOrFail();
        $this->assertContains($listing->id, $this->bookmarkedListingIds($bookmarker));

        $listingBefore = $listing->fresh()->getAttributes();
        $bookmarkBefore = $bookmark->fresh()->getAttributes();

        $this->actingAs($admin)
            ->post("/admin/users/{$owner->id}/suspend")
            ->assertRedirect();
        Notification::assertSentTo($owner, UserSuspendedNotification::class);
        $this->assertNotContains($listing->id, $this->bookmarkedListingIds($bookmarker));

        $this->assertDatabaseHas('bookmarks', ['id' => $bookmark->id]);

        $this->actingAs($admin)
            ->post("/admin/users/{$owner->id}/reactivate")
            ->assertRedirect();
        $this->assertContains($listing->id, $this->bookmarkedListingIds($bookmarker));

        $this->assertSame($listingBefore, $listing->fresh()->getAttributes());
        $this->assertSame($bookmarkBefore, $bookmark->fresh()->getAttributes());

        $owner->refresh();
        $this->assertSame(UserStatus::Active, $owner->status);
        $this->assertNull($owner->suspended_at);
    }

    // ---- Test 3 — agency lifecycle --------------------------------------

    public function test_agency_lifecycle_from_registration_to_rejection(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $this->post('/register', $this->agencyRegisterPayload('first@agency.test'))
            ->assertRedirect('/agency/setup');
        $firstUser = User::where('email', 'first@agency.test')->firstOrFail();

        $this->actingAs($firstUser)
            ->post('/agency/setup', $this->agencySetupPayload(['name' => 'First Agency']))
            ->assertRedirect('/agency');
        $agency = Agency::where('user_id', $firstUser->id)->firstOrFail();
        $this->assertSame(AgencyStatus::PendingVerification, $agency->status);

        $this->assertNotContains($agency->id, $this->directoryAgencyIds());

        $this->actingAs($firstUser->fresh())
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agency/Show')
                ->where('agency.status', 'pending_verification')
                ->where('agency.approvedAt', null));

        $this->actingAs($admin)
            ->post("/admin/agencies/{$agency->id}/approve")
            ->assertRedirect();
        Notification::assertSentTo($firstUser, AgencyApprovedNotification::class);

        $this->assertContains($agency->id, $this->directoryAgencyIds());

        $this->actingAs($firstUser->fresh())
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agency.status', 'approved')
                ->whereNot('agency.approvedAt', null));

        Auth::logout();

        $this->post('/register', $this->agencyRegisterPayload('second@agency.test'))
            ->assertRedirect('/agency/setup');
        $secondUser = User::where('email', 'second@agency.test')->firstOrFail();

        $this->actingAs($secondUser)
            ->post('/agency/setup', $this->agencySetupPayload(['name' => 'Second Agency', 'email' => 'second@agency.test']));
        $secondAgency = Agency::where('user_id', $secondUser->id)->firstOrFail();

        $this->actingAs($admin)
            ->post("/admin/agencies/{$secondAgency->id}/reject", ['reason' => 'Incomplete agency details.'])
            ->assertRedirect();
        Notification::assertSentTo($secondUser, AgencyRejectedNotification::class);
        $this->assertFalse(Agency::whereKey($secondAgency->id)->exists());

        $this->actingAs($secondUser->fresh())
            ->get('/agency')
            ->assertRedirect('/agency/setup');
    }

    // ---- Test 4 — chat / report / suspension chain ---------------------

    public function test_chat_report_and_suspension_chain(): void
    {
        Notification::fake();
        Event::fake([ChatMessageSent::class]);

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $buyer = User::factory()->regularUser()->create();
        $admin = User::factory()->admin()->create();

        $buyerListing = Listing::factory()->approved()->for($buyer, 'owner')->create();
        $this->assertContains($buyerListing->id, $this->publicListingIds());

        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Is this still available?'])
            ->assertRedirect();
        $conversation = Conversation::firstOrFail();

        $this->actingAs($owner)
            ->get("/conversations/{$conversation->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Conversations/Show'));

        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Still interested — can we talk?']);
        Event::assertDispatched(ChatMessageSent::class);

        $buyerMessage = ChatMessage::where('sender_id', $buyer->id)->latest('id')->firstOrFail();
        $this->actingAs($owner)
            ->post("/messages/{$buyerMessage->id}/report", ['reason' => 'Spam.'])
            ->assertRedirect();
        $report = ReportedChatMessage::firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/reported-messages')
            ->assertInertia(fn (Assert $page) => $page->where('reports.data.0.id', $report->id));

        $this->actingAs($admin)
            ->post("/admin/reported-messages/{$report->id}/suspend")
            ->assertRedirect();

        $report->refresh();
        $this->assertNotNull($report->resolved_at);
        $this->assertSame(UserStatus::Suspended, $buyer->refresh()->status);
        Notification::assertSentTo($buyer, UserSuspendedNotification::class);

        $this->assertFalse(class_exists('App\\Services\\SuspensionEnforcementService'));

        $this->actingAs($owner)->get("/conversations/{$conversation->id}")->assertForbidden();
        $this->actingAs($buyer)->get("/conversations/{$conversation->id}")->assertForbidden();

        $this->assertNotContains($buyerListing->id, $this->publicListingIds());
    }

    // ---- Test 5 — image pipeline integration ----------------------------

    public function test_image_pipeline_from_upload_to_deletion(): void
    {
        Storage::fake('public');

        $owner = User::factory()->regularUser()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($owner)->post('/my-listings', $this->listingPayload());
        $listing = Listing::firstOrFail();

        $this->actingAs($owner)
            ->post("/my-listings/{$listing->id}/images", [
                'image' => UploadedFile::fake()->image('photo.jpg', 1200, 900),
            ])
            ->assertRedirect();

        $image = $listing->images()->firstOrFail();
        Storage::disk('public')->assertExists($image->small_path);
        Storage::disk('public')->assertExists($image->large_path);
        $this->assertDatabaseHas('listing_images', [
            'id' => $image->id,
            'listing_id' => $listing->id,
            'disk' => 'public',
        ]);

        $this->actingAs($admin)->post("/admin/listings/{$listing->id}/approve");
        $listing->refresh();

        $expectedSmall = Storage::disk('public')->url($image->small_path);
        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $listing->id)
                ->where('listings.data.0.imageUrl', $expectedSmall));

        $expectedLarge = Storage::disk('public')->url($image->large_path);
        $this->actingAs($owner)
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('listing.images', 1)
                ->where('listing.images.0.url', $expectedLarge));

        $this->actingAs($owner)
            ->delete("/my-listings/{$listing->id}/images/{$image->id}")
            ->assertRedirect();
        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->large_path);
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);

        $this->assertSame(ListingStatus::PendingReview, $listing->fresh()->status);
        $this->actingAs($admin)->post("/admin/listings/{$listing->id}/approve")->assertRedirect();

        $this->actingAs($owner)
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page->has('listing.images', 0));

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('listings.data.0.imageUrl', null));
    }

    // ---- Test 6 — guest boundaries --------------------------------------

    public function test_guest_route_boundaries_hold(): void
    {
        $listing = Listing::factory()->approved()->create();

        $protected = [
            '/profile',
            '/agency',
            '/admin',
            "/listings/{$listing->slug}",
            '/bookmarks',
            '/conversations',
        ];

        foreach ($protected as $route) {
            $this->get($route)->assertRedirect('/login');
        }

        foreach (['/listings', '/agencies', '/'] as $route) {
            $this->get($route)->assertOk();
        }
    }

    // ---- Test 7 — admin self-protection invariants ----------------------

    public function test_admin_self_protection_invariants(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $regular = User::factory()->regularUser()->create();

        $this->actingAs($admin)->post("/admin/users/{$admin->id}/suspend")->assertForbidden();
        $this->actingAs($admin)->post("/admin/users/{$otherAdmin->id}/suspend")->assertForbidden();
        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertForbidden();
        $this->actingAs($admin)->delete("/admin/users/{$otherAdmin->id}")->assertForbidden();

        $this->actingAs($admin)->post("/admin/users/{$regular->id}/suspend")->assertRedirect();
        $this->assertTrue($regular->refresh()->isSuspended());
    }

    // ---- Test 8 — report to take-down to purge --------------------------

    public function test_report_take_down_and_purge_chain(): void
    {
        Notification::fake();
        Storage::fake('public');

        $owner = User::factory()->regularUser()->create();
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->regularUser()->create();
        $bookmarker = User::factory()->regularUser()->create();

        $this->actingAs($owner)->post('/my-listings', $this->listingPayload())->assertRedirect('/profile');
        $listing = Listing::firstOrFail();
        $this->actingAs($owner)
            ->post("/my-listings/{$listing->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg', 800, 600)])
            ->assertRedirect();
        $image = $listing->images()->firstOrFail();
        $this->actingAs($admin)->post("/admin/listings/{$listing->id}/approve")->assertRedirect();

        $this->actingAs($bookmarker)->post("/listings/{$listing->id}/bookmark")->assertRedirect();
        $this->assertContains($listing->id, $this->bookmarkedListingIds($bookmarker));

        $this->actingAs($reporter)
            ->post("/listings/{$listing->id}/report", ['reason' => ListingReportReason::Scam->value])
            ->assertSessionHas('success');
        $report = ListingReport::where('listing_id', $listing->id)->firstOrFail();
        $this->assertContains($listing->id, $this->reportQueueListingIds($admin));

        $this->actingAs($admin)
            ->post("/admin/listing-reports/{$listing->id}/take-down", ['reason' => 'Fraudulent listing.'])
            ->assertRedirect();
        Notification::assertSentTo($owner, ListingTakenDownNotification::class);

        $listing = Listing::withTrashed()->findOrFail($listing->id);
        $this->assertSame(ListingStatus::Rejected, $listing->status);
        $this->assertTrue($listing->trashed());
        $this->assertNotNull($report->fresh()->resolved_at);
        $this->assertNotContains($listing->id, $this->reportQueueListingIds($admin));
        $this->assertNotContains($listing->id, $this->publicListingIds());
        $this->assertNotContains($listing->id, $this->bookmarkedListingIds($bookmarker));
        $this->assertNotContains($listing->id, $this->unavailableBookmarkIds($bookmarker));

        $this->travel(8)->days();
        $this->artisan('listings:purge-rejected')->assertExitCode(0);

        $this->assertNull(Listing::withTrashed()->find($listing->id));
        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->large_path);
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
        $this->assertDatabaseMissing('listing_reports', ['id' => $report->id]);
        $this->assertDatabaseMissing('bookmarks', ['listing_id' => $listing->id]);

        $this->travelBack();
    }

    // ---- Test 9 — expiry and renewal of an agency listing ---------------

    public function test_agency_listing_expiry_and_renewal_across_surfaces(): void
    {
        Notification::fake();
        Event::fake([ChatMessageSent::class]);
        $this->travelTo(now()->setTime(9, 0));

        [$agencyUser, $agency] = $this->approvedAgency();
        $searcher = User::factory()->regularUser()->create();
        $this->savedSearch($searcher);

        $this->travel(1)->hour();
        $listing = $this->agencyListing($agency, ['title' => 'Riverside Agency Flat', 'expires_at' => now()->addDays(10)]);
        $anchor = $this->liveListing(['city' => 'Novi Sad']);
        $buyer = User::factory()->regularUser()->create();
        $visitor = User::factory()->regularUser()->create();

        $this->actingAs($buyer)->post("/listings/{$listing->id}/bookmark")->assertRedirect();
        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Is it still available?'])
            ->assertRedirect();
        $conversation = Conversation::where('listing_id', $listing->id)->firstOrFail();

        $this->travel(1)->day();
        $this->assertSame(['Riverside Agency Flat'], $this->digestTitles($searcher)[$searcher->id]);
        $lateSearcher = User::factory()->regularUser()->create();
        $this->savedSearch($lateSearcher, $listing->approved_at->subMinute());

        $this->travel(10)->days();
        $this->assertTrue($listing->fresh()->isExpired());

        $this->assertNotContains($listing->id, $this->publicListingIds());
        $this->assertNotContains($listing->id, $this->agencyPageListingIds($agency));
        $this->assertNotContains($listing->id, $this->similarListingIds($visitor, $anchor));
        $this->actingAs($visitor)->get("/listings/{$listing->slug}")->assertNotFound();
        $this->actingAs($visitor)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Hello?'])
            ->assertForbidden();
        $this->actingAs($visitor)
            ->post("/listings/{$listing->id}/report", ['reason' => ListingReportReason::Scam->value])
            ->assertNotFound();
        $this->assertSame(0, ListingReport::count());
        $this->assertSame([$searcher->id => [], $lateSearcher->id => []], $this->digestTitles($searcher, $lateSearcher));

        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Is it still for sale?'])
            ->assertRedirect("/conversations/{$conversation->id}");
        $this->actingAs($agencyUser)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'We will renew it.'])
            ->assertRedirect("/conversations/{$conversation->id}");
        $this->assertSame(3, $conversation->messages()->count());

        $this->actingAs($agencyUser)
            ->post("/agency/listings/{$listing->id}/renew")
            ->assertSessionHas('success');
        $this->assertTrue($listing->fresh()->expires_at->isFuture());

        $this->assertContains($listing->id, $this->publicListingIds());
        $this->assertContains($listing->id, $this->agencyPageListingIds($agency));
        $this->assertContains($listing->id, $this->similarListingIds($visitor, $anchor));
        $this->actingAs($visitor)->get("/listings/{$listing->slug}")->assertOk();
        $this->actingAs($visitor)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Hello?'])
            ->assertRedirect();
        $this->assertSame(2, Conversation::where('listing_id', $listing->id)->count());
        $this->actingAs($visitor)
            ->post("/listings/{$listing->id}/report", ['reason' => ListingReportReason::Scam->value])
            ->assertSessionHas('success');
        $this->assertSame(1, ListingReport::count());

        $this->assertSame([$searcher->id => [], $lateSearcher->id => []], $this->digestTitles($searcher, $lateSearcher));

        $this->travelBack();
    }

    // ---- Test 10 — suspension and reactivation of an agency owner -------

    public function test_agency_owner_suspension_and_reactivation_restore_every_surface(): void
    {
        Notification::fake();
        Event::fake([ChatMessageSent::class]);
        $this->travelTo(now()->setTime(9, 0));

        $admin = User::factory()->admin()->create();
        [$agencyUser, $agency] = $this->approvedAgency();
        $searcher = User::factory()->regularUser()->create();
        $this->savedSearch($searcher);

        $this->travel(1)->hour();
        $listing = $this->agencyListing($agency, ['title' => 'Suspended Agency Flat']);
        $anchor = $this->liveListing(['city' => 'Novi Sad']);
        $buyer = User::factory()->regularUser()->create();
        $visitor = User::factory()->regularUser()->create();

        $this->actingAs($buyer)->post("/listings/{$listing->id}/bookmark")->assertRedirect();
        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Is it still available?'])
            ->assertRedirect();
        $conversation = Conversation::where('listing_id', $listing->id)->firstOrFail();
        $this->actingAs($agencyUser)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Yes, it is.'])
            ->assertRedirect();

        $this->assertSame(1, $this->unreadCount($buyer));
        $this->assertContains($agency->id, $this->directoryAgencyIds());
        $this->assertTrue($this->sitemapListsAgency($agency));
        $this->assertContains($listing->id, $this->similarListingIds($visitor, $anchor));

        $listingBefore = $listing->fresh()->getAttributes();
        $agencyBefore = $agency->fresh()->getAttributes();
        $bookmarkBefore = Bookmark::firstOrFail()->getAttributes();

        $this->travel(1)->day();
        $this->actingAs($admin)->post("/admin/users/{$agencyUser->id}/suspend")->assertRedirect();

        $this->assertNull($this->agencyPageListingIds($agency));
        $this->assertNotContains($agency->id, $this->directoryAgencyIds());
        $this->assertFalse($this->sitemapListsAgency($agency));
        $this->assertNotContains($listing->id, $this->publicListingIds());
        $this->assertNotContains($listing->id, $this->similarListingIds($visitor, $anchor));
        $this->assertSame([], $this->digestTitles($searcher)[$searcher->id]);
        $this->assertSame(0, $this->unreadCount($buyer));
        $this->actingAs($buyer)->get("/conversations/{$conversation->id}")->assertForbidden();
        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Hello?'])
            ->assertForbidden();
        $this->actingAs($agencyUser->fresh())->get("/conversations/{$conversation->id}")->assertForbidden();

        $this->actingAs($admin)->post("/admin/users/{$agencyUser->id}/reactivate")->assertRedirect();

        $this->assertContains($listing->id, $this->agencyPageListingIds($agency));
        $this->assertContains($agency->id, $this->directoryAgencyIds());
        $this->assertTrue($this->sitemapListsAgency($agency));
        $this->assertContains($listing->id, $this->publicListingIds());
        $this->assertContains($listing->id, $this->similarListingIds($visitor, $anchor));
        $this->assertSame(1, $this->unreadCount($buyer));
        $this->actingAs($buyer)->get("/conversations/{$conversation->id}")->assertOk();
        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Glad you are back.'])
            ->assertRedirect("/conversations/{$conversation->id}");

        $lateSearcher = User::factory()->regularUser()->create();
        $this->savedSearch($lateSearcher, $listing->approved_at->subMinute());
        $this->assertSame(['Suspended Agency Flat'], $this->digestTitles($lateSearcher)[$lateSearcher->id]);

        $this->assertSame($listingBefore, $listing->fresh()->getAttributes());
        $this->assertSame($agencyBefore, $agency->fresh()->getAttributes());
        $this->assertSame($bookmarkBefore, Bookmark::firstOrFail()->getAttributes());

        $this->travelBack();
    }

    // ---- Test 11 — editing an approved listing sends it back to review --

    public function test_editing_an_approved_agency_listing_sends_it_back_to_review(): void
    {
        Notification::fake();
        Event::fake([ChatMessageSent::class]);
        $this->travelTo(now()->setTime(9, 0));

        $admin = User::factory()->admin()->create();
        [$agencyUser, $agency] = $this->approvedAgency();
        $searcher = User::factory()->regularUser()->create();
        $this->savedSearch($searcher);

        $this->travel(1)->hour();
        $listing = $this->agencyListing($agency, ['title' => 'Edited Agency Flat']);
        $anchor = $this->liveListing(['city' => 'Novi Sad']);
        $buyer = User::factory()->regularUser()->create();
        $reporter = User::factory()->regularUser()->create();

        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Is it still available?'])
            ->assertRedirect();
        $conversation = Conversation::where('listing_id', $listing->id)->firstOrFail();
        $this->actingAs($reporter)
            ->post("/listings/{$listing->id}/report", ['reason' => ListingReportReason::IncorrectInfo->value])
            ->assertSessionHas('success');

        $this->travel(1)->day();
        $this->assertSame(['Edited Agency Flat'], $this->digestTitles($searcher)[$searcher->id]);
        $lateSearcher = User::factory()->regularUser()->create();
        $this->savedSearch($lateSearcher, $listing->approved_at->subMinute());

        $this->actingAs($agencyUser)
            ->put("/agency/listings/{$listing->id}", $this->listingPayload(['title' => 'Edited Agency Flat', 'price' => 300000]))
            ->assertRedirect();

        $listing->refresh();
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->approved_at);
        $this->assertNull($listing->expires_at);
        $this->assertNotContains($listing->id, $this->agencyPageListingIds($agency));
        $this->assertNotContains($listing->id, $this->similarListingIds($buyer, $anchor));
        $this->assertSame([$searcher->id => [], $lateSearcher->id => []], $this->digestTitles($searcher, $lateSearcher));

        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Any news on the edit?'])
            ->assertRedirect("/conversations/{$conversation->id}");
        $this->assertSame(2, $conversation->messages()->count());

        $this->actingAs($admin)
            ->post("/admin/listing-reports/{$listing->id}/take-down", ['reason' => 'Misleading.'])
            ->assertForbidden();
        $this->assertSame(ListingStatus::PendingReview, $listing->fresh()->status);
        $this->actingAs($admin)->post("/admin/listing-reports/{$listing->id}/dismiss")->assertRedirect();
        $this->assertSame(0, $listing->openReports()->count());

        $this->travel(1)->hour();
        $this->actingAs($admin)->post("/admin/listings/{$listing->id}/approve")->assertRedirect();

        $this->assertContains($listing->id, $this->agencyPageListingIds($agency));
        $this->assertContains($listing->id, $this->similarListingIds($buyer, $anchor));
        $this->travel(1)->day();
        $this->assertSame(['Edited Agency Flat'], $this->digestTitles($searcher)[$searcher->id]);

        $this->travelBack();
    }

    // ---- Test 12 — account deletion, three paths -------------------------

    public function test_account_deletion_by_a_regular_user_cleans_up_across_features(): void
    {
        $this->assertAccountDeletionChain('regular_self');
    }

    public function test_account_deletion_by_an_agency_cleans_up_across_features(): void
    {
        $this->assertAccountDeletionChain('agency_self');
    }

    public function test_account_deletion_by_an_admin_cleans_up_across_features(): void
    {
        $this->assertAccountDeletionChain('admin');
    }

    private function assertAccountDeletionChain(string $path): void
    {
        Notification::fake();
        Storage::fake('public');
        Event::fake([ChatMessageSent::class]);

        $isAgency = $path === 'agency_self';
        $subject = $isAgency
            ? User::factory()->agency()->create()
            : User::factory()->regularUser()->create();
        $agency = $isAgency ? Agency::factory()->approved()->create(['user_id' => $subject->id]) : null;
        $listing = $isAgency ? $this->agencyListing($agency) : $this->liveListing(['user_id' => $subject->id]);
        $email = $subject->email;

        $image = ListingImage::factory()->for($listing)->create();
        Storage::disk('public')->put($image->small_path, 'small');
        Storage::disk('public')->put($image->large_path, 'large');

        $buyer = User::factory()->regularUser()->create();
        $bookmarker = User::factory()->regularUser()->create();
        $othersListing = $this->liveListing();

        $this->actingAs($bookmarker)->post("/listings/{$listing->id}/bookmark")->assertRedirect();
        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Is it still available?'])
            ->assertRedirect();
        $conversation = Conversation::where('listing_id', $listing->id)->firstOrFail();
        $this->actingAs($subject)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Yes, come and see it.'])
            ->assertRedirect();

        $buyerMessage = ChatMessage::where('sender_id', $buyer->id)->firstOrFail();
        $subjectMessage = ChatMessage::where('sender_id', $subject->id)->firstOrFail();
        $this->actingAs($buyer)->post("/messages/{$subjectMessage->id}/report", ['reason' => 'Pushy.'])->assertSessionHas('success');
        $this->actingAs($subject)->post("/messages/{$buyerMessage->id}/report", ['reason' => 'Spam.'])->assertSessionHas('success');
        $this->actingAs($subject)
            ->post("/listings/{$othersListing->id}/report", ['reason' => ListingReportReason::Duplicate->value])
            ->assertSessionHas('success');
        $this->actingAs($subject)
            ->post('/saved-searches', ['name' => 'My search', 'listingType' => 'sale', 'city' => 'Belgrade'])
            ->assertSessionHas('success');
        $this->actingAs($subject)
            ->post('/contact', ['email' => $email, 'subject' => 'Question', 'message' => 'Please call me back.'])
            ->assertRedirect('/contact');

        if ($isAgency) {
            $this->actingAs($subject)
                ->put('/agency', $this->agencySetupPayload(['name' => $agency->name.' Group']))
                ->assertSessionHas('success');
            $this->assertTrue(AgencyProfileChange::where('agency_id', $agency->id)->exists());
        }

        $this->assertSame(1, SavedSearch::where('user_id', $subject->id)->count());
        $this->assertSame(1, ListingReport::where('reported_by_user_id', $subject->id)->count());
        $this->assertSame(1, ReportedChatMessage::where('reported_by_user_id', $subject->id)->count());
        $this->assertSame(1, ReportedChatMessage::where('offending_user_id', $subject->id)->count());
        $contactMessage = ContactMessage::where('user_id', $subject->id)->firstOrFail();

        match ($path) {
            'regular_self' => $this->actingAs($subject)->delete('/profile', ['password' => 'password'])->assertRedirect('/'),
            'agency_self' => $this->actingAs($subject)->delete('/agency/account', ['password' => 'password'])->assertRedirect('/'),
            'admin' => $this->actingAs(User::factory()->admin()->create())->delete("/admin/users/{$subject->id}")->assertRedirect(),
        };

        $this->assertNull(User::find($subject->id));
        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->large_path);
        $this->assertNotContains($listing->id, $this->bookmarkedListingIds($bookmarker));
        $this->assertDatabaseMissing('bookmarks', ['listing_id' => $listing->id]);
        $this->assertSame(0, SavedSearch::where('user_id', $subject->id)->count());
        $this->assertSame(0, ListingReport::where('reported_by_user_id', $subject->id)->count());
        $this->assertSame(0, ReportedChatMessage::where('reported_by_user_id', $subject->id)->count());
        $this->assertSame(0, ReportedChatMessage::where('offending_user_id', $subject->id)->count());
        $this->assertNull($contactMessage->fresh()->user_id);

        if ($isAgency) {
            $this->assertSame(0, AgencyProfileChange::where('agency_id', $agency->id)->count());
        }

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversation.otherParticipant.name', __('app.conversations.deleted_user'))
                ->where('canReply', false));

        Notification::assertSentOnDemand(
            AccountDeletedNotification::class,
            fn (AccountDeletedNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === $email,
        );
    }

    // ---- Test 13 — pending agency profile change -------------------------

    public function test_pending_profile_change_stays_off_public_surfaces_until_approved(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        [$agencyUser, $agency] = $this->approvedAgency(['name' => 'Harbor Homes', 'email' => 'office@harbor.test']);
        $listing = $this->agencyListing($agency);
        $viewer = User::factory()->regularUser()->create();
        $slug = $agency->slug;

        $this->actingAs($agencyUser)
            ->put('/agency', $this->agencySetupPayload(['name' => 'Harbor Homes Group', 'email' => 'hello@harbor.test']))
            ->assertRedirect('/agency');
        $this->assertTrue(AgencyProfileChange::where('agency_id', $agency->id)->exists());

        $this->assertAgencyShownAs($agency, $listing, $viewer, 'Harbor Homes', 'office@harbor.test');

        $this->actingAs($admin)->post("/admin/users/{$agencyUser->id}/suspend")->assertRedirect();
        $this->actingAs($admin)->post("/admin/users/{$agencyUser->id}/reactivate")->assertRedirect();

        $this->assertTrue(AgencyProfileChange::where('agency_id', $agency->id)->exists());
        $this->assertAgencyShownAs($agency, $listing, $viewer, 'Harbor Homes', 'office@harbor.test');

        $this->actingAs($admin)->post("/admin/agencies/{$agency->id}/changes/approve")->assertRedirect();
        Notification::assertSentTo($agencyUser, AgencyChangesApprovedNotification::class);

        $this->assertSame($slug, $agency->fresh()->slug);
        $this->assertAgencyShownAs($agency, $listing, $viewer, 'Harbor Homes Group', 'hello@harbor.test');
    }

    private function assertAgencyShownAs(Agency $agency, Listing $listing, User $viewer, string $name, string $email): void
    {
        $card = collect($this->props($this->actingAs($viewer)->get('/agencies'))['agencies']['data'])->firstWhere('id', $agency->id);
        $this->assertSame([$name, $email], [$card['name'], $card['email']]);

        $page = $this->props($this->actingAs($viewer)->get("/agencies/{$agency->slug}"));
        $this->assertSame([$name, $email], [$page['agency']['name'], $page['agency']['email']]);

        $show = $this->props($this->actingAs($viewer)->get("/listings/{$listing->slug}"))['listing'];
        $this->assertSame($name, $show['agency']['name']);
        $this->assertSame([$name, $email], [$show['contactName'], $show['contactEmail']]);
    }

    // ---- Test 14 — agency listing chat against closing -------------------

    public function test_agency_listing_chat_follows_closing_and_reopening(): void
    {
        Event::fake([ChatMessageSent::class]);

        [$agencyUser, $agency] = $this->approvedAgency();
        $listing = $this->agencyListing($agency);
        $firstBuyer = User::factory()->regularUser()->create();
        $secondBuyer = User::factory()->regularUser()->create();

        $this->actingAs($firstBuyer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Is it still available?'])
            ->assertRedirect();
        $conversation = Conversation::where('listing_id', $listing->id)->firstOrFail();
        $this->assertSame($agencyUser->id, $conversation->owner_id);

        $this->actingAs($agencyUser)->post("/agency/listings/{$listing->id}/close")->assertSessionHas('success');
        $this->assertSame(ListingStatus::Sold, $listing->fresh()->status);

        $this->actingAs($secondBuyer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Can I see it?'])
            ->assertForbidden();
        $this->assertSame(1, Conversation::where('listing_id', $listing->id)->count());

        $this->actingAs($firstBuyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Congratulations on the sale.'])
            ->assertRedirect("/conversations/{$conversation->id}");
        $this->actingAs($agencyUser)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Thank you.'])
            ->assertRedirect("/conversations/{$conversation->id}");
        $this->actingAs($firstBuyer)
            ->get("/conversations/{$conversation->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversation.listing.status', ListingStatus::Sold->value)
                ->where('conversation.otherParticipant.id', $agencyUser->id)
                ->where('canReply', true));
        $this->assertSame($agencyUser->id, $conversation->fresh()->owner_id);

        $this->actingAs($agencyUser)->post("/agency/listings/{$listing->id}/reopen")->assertSessionHas('success');

        $this->actingAs($secondBuyer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Can I see it now?'])
            ->assertRedirect();
        $second = Conversation::where('listing_id', $listing->id)->where('buyer_id', $secondBuyer->id)->firstOrFail();
        $this->assertSame($agencyUser->id, $second->owner_id);
    }
}
