<?php

namespace Tests\Feature\System;

use App\Enums\AgencyStatus;
use App\Enums\ListingStatus;
use App\Enums\UserStatus;
use App\Events\ChatMessageSent;
use App\Models\Agency;
use App\Models\Bookmark;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ReportedChatMessage;
use App\Models\User;
use App\Notifications\AgencyApprovedNotification;
use App\Notifications\AgencyRejectedNotification;
use App\Notifications\ListingApprovedNotification;
use App\Notifications\ListingRejectedNotification;
use App\Notifications\UserSuspendedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
            ->assertInertia(fn (Assert $page) => $page->where('reports.0.id', $report->id));

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
}
