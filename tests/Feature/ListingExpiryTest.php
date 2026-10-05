<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Models\Agency;
use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingExpiringNotification;
use App\Services\ListingService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingExpiryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function service(): ListingService
    {
        return app(ListingService::class);
    }

    private function approvedAgency(): array
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        return [$user, $agency];
    }

    private function agencyListing(Agency $agency, array $attributes = []): Listing
    {
        return Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
            ...$attributes,
        ]);
    }

    // ---- Visibility ----------------------------------------------------

    public function test_expired_listing_is_absent_from_every_public_surface(): void
    {
        [, $agency] = $this->approvedAgency();
        $listing = $this->agencyListing($agency, ['expires_at' => now()->subMinute()]);
        $viewer = User::factory()->regularUser()->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));

        $this->get("/agencies/{$agency->slug}")
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));

        $this->actingAs($viewer)->get("/listings/{$listing->slug}")->assertNotFound();
        $this->actingAs($viewer)->post("/listings/{$listing->id}/bookmark")->assertNotFound();

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_expired_user_listing_cannot_be_chatted_on(): void
    {
        $listing = Listing::factory()->expired()->create();
        $viewer = User::factory()->regularUser()->create();

        $this->actingAs($viewer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Is this still available?'])
            ->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_listing_is_hidden_exactly_at_its_expiry_time(): void
    {
        $this->freezeSecond();

        $listing = Listing::factory()->approved()->create(['expires_at' => now()->addMinute()]);

        $this->get('/listings')->assertInertia(fn (Assert $page) => $page->has('listings.data', 1));
        $this->assertTrue($listing->fresh()->isPubliclyVisible());

        $this->travel(1)->minute();

        $this->get('/listings')->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));
        $this->assertFalse($listing->fresh()->isPubliclyVisible());
        $this->assertTrue($listing->fresh()->isExpired());
    }

    public function test_listing_expiring_tomorrow_is_fully_visible(): void
    {
        $listing = Listing::factory()->approved()->create(['expires_at' => now()->addDay()]);
        $viewer = User::factory()->regularUser()->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $listing->id)
            );

        $this->actingAs($viewer)->get("/listings/{$listing->slug}")->assertOk();
        $this->actingAs($viewer)->post("/listings/{$listing->id}/bookmark")->assertRedirect();

        $this->assertDatabaseHas('bookmarks', ['user_id' => $viewer->id, 'listing_id' => $listing->id]);
    }

    public function test_approved_listing_without_expiry_date_stays_visible(): void
    {
        $listing = Listing::factory()->approved()->create(['expires_at' => null]);

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('listings.data.0.id', $listing->id));

        $this->assertFalse($listing->isExpired());
        $this->assertTrue($listing->isPubliclyVisible());
    }

    // ---- Favorites -----------------------------------------------------

    public function test_expired_bookmark_moves_to_the_no_longer_available_group_and_can_be_removed(): void
    {
        $viewer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->expired()->create();
        Bookmark::factory()->create(['user_id' => $viewer->id, 'listing_id' => $listing->id]);

        $this->actingAs($viewer)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 0)
                ->has('closedListings', 1)
                ->where('closedListings.0.id', $listing->id)
                ->where('closedListings.0.status', 'approved')
                ->where('closedListings.0.canBookmark', true)
            );

        $this->actingAs($viewer)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.bookmarks.flash_removed'));

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_renewed_bookmark_returns_to_the_main_favorites_list(): void
    {
        $viewer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->expired()->create();
        Bookmark::factory()->create(['user_id' => $viewer->id, 'listing_id' => $listing->id]);

        $this->service()->renew($listing);

        $this->actingAs($viewer)
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings', 1)
                ->where('listings.0.id', $listing->id)
                ->has('closedListings', 0)
            );
    }

    // ---- Lifecycle timestamps -----------------------------------------

    public function test_approval_starts_a_ninety_day_lifetime_and_clears_the_reminder_marker(): void
    {
        $this->freezeSecond();

        $listing = Listing::factory()->pendingReview()->create(['expiry_reminder_sent_at' => now()->subDay()]);

        $this->service()->approve($listing);
        $listing->refresh();

        $this->assertTrue($listing->expires_at->equalTo(now()->addDays(90)));
        $this->assertNull($listing->expiry_reminder_sent_at);
    }

    public function test_editing_an_approved_listing_clears_expiry_and_reapproval_sets_it_again(): void
    {
        $this->freezeSecond();

        $listing = Listing::factory()->approved()->create(['expiry_reminder_sent_at' => now()]);

        $this->service()->update($listing, ['title' => $listing->title, 'price' => 123456]);
        $listing->refresh();

        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->expires_at);
        $this->assertNull($listing->expiry_reminder_sent_at);

        $this->travel(3)->days();
        $this->service()->approve($listing);

        $this->assertTrue($listing->refresh()->expires_at->equalTo(now()->addDays(90)));
    }

    public function test_editing_an_approved_agency_listing_clears_expiry(): void
    {
        [, $agency] = $this->approvedAgency();
        $listing = $this->agencyListing($agency);

        $this->service()->updateForAgency($listing, ['title' => $listing->title]);

        $this->assertNull($listing->refresh()->expires_at);
    }

    public function test_reopening_a_closed_listing_starts_a_fresh_lifetime(): void
    {
        $this->freezeSecond();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->sold()->for($owner, 'owner')->create([
            'expires_at' => now()->subDays(20),
            'expiry_reminder_sent_at' => now()->subDays(27),
        ]);

        $this->actingAs($owner)->post("/my-listings/{$listing->id}/reopen")->assertRedirect();
        $listing->refresh();

        $this->assertTrue($listing->expires_at->equalTo(now()->addDays(90)));
        $this->assertNull($listing->expiry_reminder_sent_at);
        $this->assertTrue($listing->isPubliclyVisible());
    }

    public function test_closing_leaves_expiry_untouched(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $expiresAt = $listing->expires_at->toIso8601String();

        $this->actingAs($owner)->post("/my-listings/{$listing->id}/close")->assertRedirect();

        $this->assertSame($expiresAt, $listing->refresh()->expires_at->toIso8601String());
    }

    // ---- Renew ---------------------------------------------------------

    public function test_owner_renews_listing_inside_the_reminder_window(): void
    {
        $this->freezeSecond();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create([
            'expires_at' => now()->addDays(3),
            'expiry_reminder_sent_at' => now()->subDays(4),
        ]);
        $approvedAt = $listing->approved_at->toIso8601String();

        $this->actingAs($owner)
            ->from('/profile')
            ->post("/my-listings/{$listing->id}/renew")
            ->assertRedirect('/profile')
            ->assertSessionHas('success', __('app.profile.flash_renewed', [
                'date' => now()->addDays(90)->format('F j, Y'),
            ]));

        $listing->refresh();

        $this->assertTrue($listing->expires_at->equalTo(now()->addDays(90)));
        $this->assertNull($listing->expiry_reminder_sent_at);
        $this->assertSame(ListingStatus::Approved, $listing->status);
        $this->assertSame($approvedAt, $listing->approved_at->toIso8601String());
    }

    public function test_owner_renews_an_expired_listing_and_it_is_public_again(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->expired()->for($owner, 'owner')->create();

        $this->actingAs($owner)->post("/my-listings/{$listing->id}/renew")->assertRedirect();

        $this->assertFalse($listing->refresh()->isExpired());
        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('listings.data.0.id', $listing->id));
    }

    public function test_agency_owner_renews_agency_listing(): void
    {
        $this->freezeSecond();

        [$user, $agency] = $this->approvedAgency();
        $listing = $this->agencyListing($agency, ['expires_at' => now()->addDays(2)]);

        $this->actingAs($user)
            ->post("/agency/listings/{$listing->id}/renew")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.agency_show.listings.flash_renewed', [
                'date' => now()->addDays(90)->format('F j, Y'),
            ]));

        $this->assertTrue($listing->refresh()->expires_at->equalTo(now()->addDays(90)));
    }

    public function test_renew_window_opens_exactly_seven_days_before_expiry(): void
    {
        $this->freezeSecond();

        $owner = User::factory()->regularUser()->create();
        $sevenDaysOut = Listing::factory()->approved()->for($owner, 'owner')->create(['expires_at' => now()->addDays(7)]);
        $eightDaysOut = Listing::factory()->approved()->for($owner, 'owner')->create(['expires_at' => now()->addDays(8)]);
        $eightDaysExpiry = $eightDaysOut->expires_at->toIso8601String();

        $this->actingAs($owner)->post("/my-listings/{$sevenDaysOut->id}/renew")->assertRedirect();
        $this->actingAs($owner)->post("/my-listings/{$eightDaysOut->id}/renew")->assertForbidden();

        $this->assertTrue($sevenDaysOut->refresh()->expires_at->equalTo(now()->addDays(90)));
        $this->assertSame($eightDaysExpiry, $eightDaysOut->refresh()->expires_at->toIso8601String());
    }

    public function test_pending_and_closed_listings_cannot_be_renewed(): void
    {
        $owner = User::factory()->regularUser()->create();

        foreach (['pendingReview', 'hidden', 'sold', 'rented'] as $state) {
            $listing = Listing::factory()->{$state}()->for($owner, 'owner')->create([
                'expires_at' => now()->subDay(),
            ]);

            $this->actingAs($owner)
                ->post("/my-listings/{$listing->id}/renew")
                ->assertForbidden();
        }
    }

    public function test_other_users_cannot_renew_a_listing(): void
    {
        $listing = Listing::factory()->expired()->create();
        [$agencyUser] = $this->approvedAgency();

        $this->actingAs(User::factory()->regularUser()->create())
            ->post("/my-listings/{$listing->id}/renew")
            ->assertForbidden();

        $this->actingAs($agencyUser)
            ->post("/agency/listings/{$listing->id}/renew")
            ->assertForbidden();

        $this->assertTrue($listing->refresh()->isExpired());
    }

    public function test_other_agency_cannot_renew_agency_listing(): void
    {
        [, $agency] = $this->approvedAgency();
        [$otherUser] = $this->approvedAgency();
        $listing = $this->agencyListing($agency, ['expires_at' => now()->subDay()]);

        $this->actingAs($otherUser)
            ->post("/agency/listings/{$listing->id}/renew")
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_when_renewing(): void
    {
        $listing = Listing::factory()->expired()->create();

        $this->post("/my-listings/{$listing->id}/renew")->assertRedirect('/login');
    }

    public function test_service_refuses_to_renew_outside_the_window(): void
    {
        $listing = Listing::factory()->approved()->create(['expires_at' => now()->addDays(30)]);

        $this->expectException(InvalidListingStatusTransitionException::class);

        $this->service()->renew($listing);
    }

    public function test_service_refuses_to_renew_a_pending_listing(): void
    {
        $listing = Listing::factory()->pendingReview()->create();

        $this->expectException(InvalidListingStatusTransitionException::class);

        $this->service()->renew($listing);
    }

    // ---- Payloads ------------------------------------------------------

    public function test_owner_dashboard_exposes_expiry_state(): void
    {
        $this->freezeSecond();

        $owner = User::factory()->regularUser()->create();
        $fresh = Listing::factory()->approved()->for($owner, 'owner')->create([
            'expires_at' => now()->addDays(60),
            'created_at' => now()->subDays(3),
        ]);
        $expiring = Listing::factory()->approved()->for($owner, 'owner')->create([
            'expires_at' => now()->addDays(5),
            'created_at' => now()->subDays(2),
        ]);
        $expired = Listing::factory()->expired()->for($owner, 'owner')->create([
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.0.id', $expired->id)
                ->where('listings.0.isExpired', true)
                ->where('listings.0.canRenew', true)
                ->where('listings.1.id', $expiring->id)
                ->where('listings.1.isExpired', false)
                ->where('listings.1.canRenew', true)
                ->where('listings.1.expiresAt', now()->addDays(5)->toIso8601String())
                ->where('listings.2.id', $fresh->id)
                ->where('listings.2.isExpired', false)
                ->where('listings.2.canRenew', false)
                ->where('translations.renew', 'Renew for 90 days')
            );
    }

    public function test_agency_dashboard_exposes_expiry_state(): void
    {
        [$user, $agency] = $this->approvedAgency();
        $this->agencyListing($agency, ['expires_at' => now()->subHour()]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.0.isExpired', true)
                ->where('listings.0.canRenew', true)
                ->has('listings.0.expiresAt')
                ->where('translations.listings.expired', 'Expired')
            );
    }

    public function test_admin_review_page_shows_expiry(): void
    {
        $this->freezeSecond();

        $listing = Listing::factory()->approved()->create(['expires_at' => now()->addDays(40)]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.expiresAt', now()->addDays(40)->toIso8601String())
                ->where('translations.review.expires_label', 'Expires')
            );
    }

    public function test_admin_review_page_hides_stale_expiry_of_closed_listing(): void
    {
        $listing = Listing::factory()->sold()->create(['expires_at' => now()->addDays(40)]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertInertia(fn (Assert $page) => $page->where('listing.expiresAt', null));
    }

    // ---- Reminder command ---------------------------------------------

    public function test_reminder_command_emails_listings_expiring_within_seven_days_once(): void
    {
        Notification::fake();
        $this->freezeSecond();

        $owner = User::factory()->regularUser()->create();
        $due = Listing::factory()->approved()->for($owner, 'owner')->create(['expires_at' => now()->addDays(7)]);
        Listing::factory()->approved()->for($owner, 'owner')->create(['expires_at' => now()->addDays(8)]);
        Listing::factory()->approved()->for($owner, 'owner')->create([
            'expires_at' => now()->addDays(2),
            'expiry_reminder_sent_at' => now()->subDays(5),
        ]);
        Listing::factory()->expired()->for($owner, 'owner')->create();

        $this->artisan('listings:send-expiry-reminders')
            ->expectsOutput('Sent 1 expiry reminders.')
            ->assertSuccessful();

        Notification::assertSentToTimes($owner, ListingExpiringNotification::class, 1);
        Notification::assertSentTo($owner, ListingExpiringNotification::class, fn (ListingExpiringNotification $notification) => $notification->listingTitle === $due->title
            && $notification->expiresOn === now()->addDays(7)->format('F j, Y')
            && $notification->dashboardUrl === route('profile.index'));

        $this->assertTrue($due->refresh()->expiry_reminder_sent_at->equalTo(now()));
    }

    public function test_reminder_command_is_idempotent(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        Listing::factory()->approved()->for($owner, 'owner')->create(['expires_at' => now()->addDays(3)]);

        $this->artisan('listings:send-expiry-reminders')->expectsOutput('Sent 1 expiry reminders.');
        $this->artisan('listings:send-expiry-reminders')->expectsOutput('Sent 0 expiry reminders.');

        Notification::assertSentToTimes($owner, ListingExpiringNotification::class, 1);
    }

    public function test_reminder_command_sends_agency_listings_to_the_agency_owner(): void
    {
        Notification::fake();

        [$user, $agency] = $this->approvedAgency();
        $this->agencyListing($agency, ['expires_at' => now()->addDays(4)]);

        $this->artisan('listings:send-expiry-reminders')->assertSuccessful();

        Notification::assertSentTo($user, ListingExpiringNotification::class, fn (ListingExpiringNotification $notification) => $notification->dashboardUrl === route('agency.index'));
    }

    public function test_reminder_command_skips_suspended_owners_and_closed_or_pending_listings(): void
    {
        Notification::fake();

        $suspended = User::factory()->regularUser()->suspended()->create();
        Listing::factory()->approved()->for($suspended, 'owner')->create(['expires_at' => now()->addDays(3)]);

        $suspendedAgencyUser = User::factory()->agency()->suspended()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $suspendedAgencyUser->id]);
        $this->agencyListing($agency, ['expires_at' => now()->addDays(3)]);

        $owner = User::factory()->regularUser()->create();
        Listing::factory()->sold()->for($owner, 'owner')->create(['expires_at' => now()->addDays(3)]);
        Listing::factory()->pendingReview()->for($owner, 'owner')->create(['expires_at' => now()->addDays(3)]);

        $this->artisan('listings:send-expiry-reminders')
            ->expectsOutput('Sent 0 expiry reminders.')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_reminder_command_is_scheduled_daily(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event) => str_contains((string) $event->command, 'listings:send-expiry-reminders'));

        $this->assertNotNull($event);
        $this->assertSame('0 8 * * *', $event->expression);
    }

    public function test_reminder_schedule_does_not_overlap(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event) => str_contains((string) $event->command, 'listings:send-expiry-reminders'));

        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(60, $event->expiresAt);
    }

    public function test_expiring_email_links_to_the_dashboard(): void
    {
        $notification = new ListingExpiringNotification('Sunny Loft', 'October 5, 2026', 'https://example.test/profile');
        $mail = $notification->toMail(User::factory()->make());

        $this->assertSame(__('app.emails.listing_expiring.subject'), $mail->subject);
        $this->assertSame('Renew listing', $mail->actionText);
        $this->assertSame('https://example.test/profile', $mail->actionUrl);
        $this->assertStringContainsString('Sunny Loft', $mail->introLines[0]);
        $this->assertStringContainsString('October 5, 2026', $mail->introLines[0]);
        $this->assertStringContainsString('without a new review', $mail->outroLines[0]);
    }

    // ---- Backfill ------------------------------------------------------

    public function test_backfill_gives_approved_listings_ninety_days_from_the_migration(): void
    {
        $this->freezeSecond();

        $approved = Listing::factory()->approved()->create(['expires_at' => null]);
        $trashed = Listing::factory()->approved()->create(['expires_at' => null]);
        $trashed->delete();
        $alreadySet = Listing::factory()->approved()->create(['expires_at' => now()->addDays(10)]);
        $pending = Listing::factory()->pendingReview()->create();
        $sold = Listing::factory()->sold()->create(['expires_at' => null]);

        $migration = require database_path('migrations/2026_09_28_192248_backfill_listing_expires_at.php');
        $migration->up();

        $rows = DB::table('listings')->get()->keyBy('id');
        $expected = now()->addDays(90)->toDateTimeString();

        $this->assertSame($expected, $rows[$approved->id]->expires_at);
        $this->assertSame($expected, $rows[$trashed->id]->expires_at);
        $this->assertSame(now()->addDays(10)->toDateTimeString(), $rows[$alreadySet->id]->expires_at);
        $this->assertNull($rows[$pending->id]->expires_at);
        $this->assertNull($rows[$sold->id]->expires_at);
    }
}
