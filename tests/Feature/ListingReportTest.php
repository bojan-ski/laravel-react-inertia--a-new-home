<?php

namespace Tests\Feature;

use App\Enums\ListingReportResolution;
use App\Enums\ListingStatus;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Models\Agency;
use App\Models\Listing;
use App\Models\ListingReport;
use App\Models\User;
use App\Notifications\ListingRejectedNotification;
use App\Notifications\ListingTakenDownNotification;
use App\Services\ListingReportService;
use App\Services\ListingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function agencyListing(Agency $agency, string $state = 'approved'): Listing
    {
        return Listing::factory()->{$state}()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);
    }

    private function reportFor(Listing $listing, ?User $reporter = null): ListingReport
    {
        return ListingReport::factory()->create([
            'listing_id' => $listing->id,
            'reported_by_user_id' => ($reporter ?? User::factory()->regularUser()->create())->id,
        ]);
    }

    // ---- Reporting ------------------------------------------------------

    public function test_user_can_report_a_visible_listing(): void
    {
        $listing = Listing::factory()->approved()->create();
        $reporter = User::factory()->regularUser()->create();

        $this->actingAs($reporter)
            ->post("/listings/{$listing->id}/report", ['reason' => 'scam', 'details' => 'Asks for a deposit by wire.'])
            ->assertRedirect()
            ->assertSessionHas('success', __('app.listing_reports.flash.reported'));

        $report = ListingReport::sole();

        $this->assertSame($listing->id, $report->listing_id);
        $this->assertSame($reporter->id, $report->reported_by_user_id);
        $this->assertSame('scam', $report->reason->value);
        $this->assertSame('Asks for a deposit by wire.', $report->details);
        $this->assertNull($report->resolved_at);
        $this->assertNull($report->resolved_by_user_id);
        $this->assertNull($report->resolution);
    }

    public function test_agency_user_can_report_another_owners_listing(): void
    {
        $listing = Listing::factory()->approved()->create();
        $agencyUser = Agency::factory()->approved()->create()->user;

        $this->actingAs($agencyUser)
            ->post("/listings/{$listing->id}/report", ['reason' => 'duplicate'])
            ->assertSessionHas('success');

        $this->assertSame(1, ListingReport::count());
    }

    public function test_details_are_required_when_the_reason_is_other(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->post("/listings/{$listing->id}/report", ['reason' => 'other', 'details' => ''])
            ->assertSessionHasErrors(['details' => __('app.listing_reports.validation.details_required')]);

        $this->assertSame(0, ListingReport::count());
    }

    public function test_other_reason_with_details_is_accepted(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->post("/listings/{$listing->id}/report", ['reason' => 'other', 'details' => 'Photos are from another city.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('other', ListingReport::sole()->reason->value);
    }

    public function test_unknown_or_missing_reason_fails_validation(): void
    {
        $listing = Listing::factory()->approved()->create();
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/report", ['reason' => 'boring'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/report", [])
            ->assertSessionHasErrors('reason');

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/report", ['reason' => 'scam', 'details' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('details');

        $this->assertSame(0, ListingReport::count());
    }

    public function test_reporter_cannot_set_server_controlled_fields(): void
    {
        $listing = Listing::factory()->approved()->create();
        $other = Listing::factory()->approved()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->post("/listings/{$listing->id}/report", [
                'reason' => 'scam',
                'listing_id' => $other->id,
                'reported_by_user_id' => $admin->id,
                'resolved_at' => now()->toDateTimeString(),
                'resolution' => 'dismissed',
            ]);

        $report = ListingReport::sole();

        $this->assertSame($listing->id, $report->listing_id);
        $this->assertNotSame($admin->id, $report->reported_by_user_id);
        $this->assertNull($report->resolved_at);
        $this->assertNull($report->resolution);
    }

    public function test_owner_cannot_report_their_own_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->post("/listings/{$listing->id}/report", ['reason' => 'scam'])
            ->assertForbidden();

        $this->assertSame(0, ListingReport::count());
    }

    public function test_agency_owner_cannot_report_their_agency_listing(): void
    {
        $agency = Agency::factory()->approved()->create();
        $listing = $this->agencyListing($agency);

        $this->actingAs($agency->user)
            ->post("/listings/{$listing->id}/report", ['reason' => 'scam'])
            ->assertForbidden();

        $this->assertSame(0, ListingReport::count());
    }

    public function test_admin_cannot_report_a_listing(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/listings/{$listing->id}/report", ['reason' => 'scam'])
            ->assertForbidden();

        $this->assertSame(0, ListingReport::count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->post("/listings/{$listing->id}/report", ['reason' => 'scam'])
            ->assertRedirect('/login');

        $this->assertSame(0, ListingReport::count());
    }

    public function test_suspended_user_is_logged_out_and_redirected(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->suspended()->create())
            ->post("/listings/{$listing->id}/report", ['reason' => 'scam'])
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertSame(0, ListingReport::count());
    }

    public function test_listings_that_are_not_publicly_visible_return_404(): void
    {
        $user = User::factory()->regularUser()->create();

        $listings = [
            Listing::factory()->hidden()->create(),
            Listing::factory()->pendingReview()->create(),
            Listing::factory()->sold()->create(),
            Listing::factory()->expired()->create(),
            Listing::factory()->approved()->for(User::factory()->regularUser()->suspended(), 'owner')->create(),
        ];

        $rejected = Listing::factory()->rejected()->create();
        $rejected->delete();
        $listings[] = $rejected;

        foreach ($listings as $listing) {
            $this->actingAs($user)
                ->post("/listings/{$listing->id}/report", ['reason' => 'scam'])
                ->assertNotFound();
        }

        $this->assertSame(0, ListingReport::count());
    }

    public function test_invalid_payload_on_a_hidden_listing_returns_404_not_validation_errors(): void
    {
        $listing = Listing::factory()->hidden()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->post("/listings/{$listing->id}/report", ['reason' => 'boring'])
            ->assertNotFound()
            ->assertSessionHasNoErrors();
    }

    public function test_invalid_payload_from_the_owner_is_forbidden_before_validation(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->post("/listings/{$listing->id}/report", [])
            ->assertForbidden();
    }

    public function test_second_report_by_the_same_user_is_rejected(): void
    {
        $listing = Listing::factory()->approved()->create();
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->post("/listings/{$listing->id}/report", ['reason' => 'scam']);

        $this->actingAs($user)
            ->post("/listings/{$listing->id}/report", ['reason' => 'duplicate'])
            ->assertRedirect()
            ->assertSessionHas('error', __('app.listing_reports.errors.duplicate'));

        $this->assertSame(1, ListingReport::count());
    }

    public function test_database_blocks_duplicate_reports(): void
    {
        $listing = Listing::factory()->approved()->create();
        $user = User::factory()->regularUser()->create();

        $this->reportFor($listing, $user);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->reportFor($listing, $user);
    }

    public function test_different_users_can_report_the_same_listing(): void
    {
        $listing = Listing::factory()->approved()->create();

        foreach (User::factory()->regularUser()->count(2)->create() as $user) {
            $this->actingAs($user)
                ->post("/listings/{$listing->id}/report", ['reason' => 'scam'])
                ->assertSessionHas('success');
        }

        $this->assertSame(2, $listing->openReports()->count());
    }

    public function test_eleventh_report_in_an_hour_is_throttled(): void
    {
        $user = User::factory()->regularUser()->create();
        $headers = ['X-Inertia' => 'true', 'Referer' => url('/listings')];
        $listings = Listing::factory()->approved()->count(11)->create();

        foreach ($listings->take(10) as $listing) {
            $this->actingAs($user)
                ->post("/listings/{$listing->id}/report", ['reason' => 'scam'], $headers)
                ->assertSessionHas('success');
        }

        $this->actingAs($user)
            ->post("/listings/{$listings->last()->id}/report", ['reason' => 'scam'], $headers)
            ->assertRedirect('/listings')
            ->assertSessionHas('throttled');

        $this->assertSame(10, ListingReport::count());
    }

    public function test_listing_page_exposes_can_report(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('canReport', true)
                ->where('reportTranslations.action', __('app.listing_reports.action'))
                ->where('reportTranslations.reasons.other', __('app.listing_reports.reasons.other'))
            );

        $this->actingAs($owner)
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('canReport', false));

        $this->actingAs(User::factory()->admin()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('canReport', false));
    }

    public function test_reason_labels_cover_every_enum_case(): void
    {
        $this->assertSame(
            ['scam', 'incorrect_info', 'no_longer_available', 'duplicate', 'offensive', 'other'],
            array_keys(__('app.listing_reports.reasons')),
        );
    }

    // ---- Admin queue ----------------------------------------------------

    public function test_queue_lists_only_listings_with_open_reports(): void
    {
        $admin = User::factory()->admin()->create();

        $reported = Listing::factory()->approved()->create();
        $this->reportFor($reported);
        $this->reportFor($reported);

        $resolvedOnly = Listing::factory()->approved()->create();
        ListingReport::factory()->resolved()->create(['listing_id' => $resolvedOnly->id]);

        $ownerDeleted = Listing::factory()->approved()->create();
        $this->reportFor($ownerDeleted);
        $ownerDeleted->delete();

        Listing::factory()->approved()->create();

        $this->actingAs($admin)
            ->get('/admin/listing-reports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ListingReports/Index')
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $reported->id)
                ->where('listings.data.0.openReportsCount', 2)
                ->has('listings.data.0.reports', 2)
                ->where('listings.data.0.canTakeDown', true)
                ->where('translations.title', __('app.admin.listing_reports.title'))
                ->where('reasonLabels.scam', __('app.listing_reports.reasons.scam'))
            );
    }

    public function test_queue_orders_by_newest_open_report(): void
    {
        $admin = User::factory()->admin()->create();

        $older = Listing::factory()->approved()->create();
        $newer = Listing::factory()->approved()->create();

        $this->travelTo(now()->subDays(3));
        $this->reportFor($older);
        $this->travelBack();

        $this->travelTo(now()->subDay());
        $this->reportFor($newer);
        $this->travelBack();

        $this->travelTo(now()->subDays(5));
        $this->reportFor($newer);
        $this->travelBack();

        $this->actingAs($admin)
            ->get('/admin/listing-reports')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $newer->id)
                ->where('listings.data.1.id', $older->id)
            );
    }

    public function test_queue_payload_has_the_documented_keys(): void
    {
        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->approved()->create();
        $this->reportFor($listing);

        $this->actingAs($admin)
            ->get('/admin/listing-reports')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data.0', fn (Assert $row) => $row
                    ->hasAll(['id', 'title', 'status', 'listingType', 'price', 'currency', 'city', 'country', 'imageUrl', 'ownerName', 'ownerEmail', 'openReportsCount', 'reports', 'canTakeDown'])
                    ->has('reports.0', fn (Assert $report) => $report
                        ->hasAll(['id', 'reason', 'details', 'reportedByName', 'createdAt'])
                    )
                )
            );
    }

    public function test_queue_payload_carries_the_listing_summary_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = Listing::factory()->approved()->create([
            'listing_type' => 'sale',
            'price' => 1290000,
            'city' => 'Split',
            'country' => 'Croatia',
        ]);
        $this->reportFor($sale);

        $this->actingAs($admin)
            ->get('/admin/listing-reports')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $sale->id)
                ->where('listings.data.0.listingType', 'sale')
                ->where('listings.data.0.price', '1290000.00')
                ->where('listings.data.0.currency', Listing::CURRENCY)
                ->where('listings.data.0.city', 'Split')
                ->where('listings.data.0.country', 'Croatia')
                ->where('translations.listing_types.sale', __('app.admin.listing_reports.listing_types.sale'))
                ->where('translations.listing_types.rent', __('app.admin.listing_reports.listing_types.rent'))
                ->where('translations.per_month', __('app.admin.listing_reports.per_month'))
                ->where('translations.view_listing', __('app.admin.listing_reports.view_listing'))
            );
    }

    public function test_review_page_report_payload_carries_the_listing_summary_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $rent = Listing::factory()->approved()->create([
            'listing_type' => 'rent',
            'price' => 2400,
            'city' => 'Berlin',
            'country' => 'Germany',
        ]);
        $this->reportFor($rent);

        $this->actingAs($admin)
            ->get("/admin/listings/{$rent->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('reportModeration.listingType', 'rent')
                ->where('reportModeration.price', '2400.00')
                ->where('reportModeration.city', 'Berlin')
                ->where('reportModeration.country', 'Germany')
            );
    }

    public function test_non_admins_cannot_moderate_reports(): void
    {
        $listing = Listing::factory()->approved()->create();
        $this->reportFor($listing);

        foreach ([User::factory()->regularUser()->create(), Agency::factory()->approved()->create()->user] as $user) {
            $this->actingAs($user)->get('/admin/listing-reports')->assertForbidden();
            $this->actingAs($user)->post("/admin/listing-reports/{$listing->id}/dismiss")->assertForbidden();
            $this->actingAs($user)
                ->post("/admin/listing-reports/{$listing->id}/take-down", ['reason' => 'Scam'])
                ->assertForbidden();
        }

        $this->assertTrue($listing->fresh()->isApproved());
        $this->assertSame(1, $listing->openReports()->count());
    }

    public function test_dismiss_resolves_every_open_report_and_keeps_the_listing(): void
    {
        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->approved()->create();
        $open = [$this->reportFor($listing), $this->reportFor($listing)];
        $earlier = ListingReport::factory()->resolved(ListingReportResolution::Dismissed)->create([
            'listing_id' => $listing->id,
            'resolved_at' => now()->subWeek(),
        ]);
        $snapshot = fn (): array => Listing::withTrashed()->findOrFail($listing->id)
            ->getRawOriginal();
        $before = $snapshot();

        $this->actingAs($admin)
            ->post("/admin/listing-reports/{$listing->id}/dismiss")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.admin.listing_reports.flash.dismissed'));

        foreach ($open as $report) {
            $report->refresh();
            $this->assertSame(ListingReportResolution::Dismissed, $report->resolution);
            $this->assertSame($admin->id, $report->resolved_by_user_id);
            $this->assertNotNull($report->resolved_at);
        }

        $this->assertTrue($earlier->fresh()->resolved_at->lessThan(now()->subDays(6)));
        $this->assertSame($before, $snapshot());
    }

    public function test_dismiss_without_open_reports_is_forbidden(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listing-reports/{$listing->id}/dismiss")
            ->assertForbidden();
    }

    public function test_dismiss_works_on_a_listing_that_is_no_longer_approved(): void
    {
        $listing = Listing::factory()->approved()->create();
        $this->reportFor($listing);
        $listing->forceFill(['status' => ListingStatus::PendingReview, 'approved_at' => null])->save();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listing-reports/{$listing->id}/dismiss")
            ->assertSessionHas('success');

        $this->assertSame(0, $listing->openReports()->count());
    }

    public function test_take_down_rejects_the_listing_resolves_reports_and_emails_the_owner(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $reports = [$this->reportFor($listing), $this->reportFor($listing)];

        $this->actingAs($admin)
            ->post("/admin/listing-reports/{$listing->id}/take-down", ['reason' => 'Confirmed scam.'])
            ->assertRedirect()
            ->assertSessionHas('success', __('app.admin.listing_reports.flash.taken_down'));

        $listing = Listing::withTrashed()->findOrFail($listing->id);

        $this->assertSame(ListingStatus::Rejected, $listing->status);
        $this->assertSame('Confirmed scam.', $listing->rejection_reason);
        $this->assertNotNull($listing->rejected_at);
        $this->assertNull($listing->approved_at);
        $this->assertTrue($listing->trashed());

        foreach ($reports as $report) {
            $report->refresh();
            $this->assertSame(ListingReportResolution::TakenDown, $report->resolution);
            $this->assertSame($admin->id, $report->resolved_by_user_id);
            $this->assertNotNull($report->resolved_at);
        }

        Notification::assertSentTo($owner, ListingTakenDownNotification::class, fn (ListingTakenDownNotification $notification): bool => $notification->listingTitle === $listing->title
            && $notification->reason === 'Confirmed scam.');
        Notification::assertNotSentTo($owner, ListingRejectedNotification::class);
    }

    public function test_take_down_emails_the_agency_owner_for_agency_listings(): void
    {
        Notification::fake();

        $agency = Agency::factory()->approved()->create();
        $listing = $this->agencyListing($agency);
        $this->reportFor($listing);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listing-reports/{$listing->id}/take-down", ['reason' => 'Duplicate of another listing.']);

        Notification::assertSentTo($agency->user, ListingTakenDownNotification::class);
    }

    public function test_take_down_email_uses_the_taken_down_copy(): void
    {
        $mail = (new ListingTakenDownNotification('Sunny Flat', 'Confirmed scam.'))
            ->toMail(User::factory()->make());

        $this->assertSame(__('app.emails.listing_taken_down.subject'), $mail->subject);
        $this->assertContains(__('app.emails.listing_taken_down.reason', ['reason' => 'Confirmed scam.']), $mail->introLines);
    }

    public function test_take_down_requires_a_reason(): void
    {
        $listing = Listing::factory()->approved()->create();
        $this->reportFor($listing);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listing-reports/{$listing->id}/take-down", ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertTrue($listing->fresh()->isApproved());
    }

    public function test_take_down_is_denied_for_listings_that_are_not_approved(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        foreach (['pendingReview', 'sold', 'hidden'] as $state) {
            $listing = Listing::factory()->{$state}()->create();
            $this->reportFor($listing);

            $this->actingAs($admin)
                ->post("/admin/listing-reports/{$listing->id}/take-down", ['reason' => 'Scam'])
                ->assertForbidden();

            $this->assertFalse($listing->fresh()->isRejected());
            $this->assertSame(1, $listing->openReports()->count());
        }

        Notification::assertNothingSent();
    }

    public function test_take_down_without_open_reports_is_forbidden(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listing-reports/{$listing->id}/take-down", ['reason' => 'Scam'])
            ->assertForbidden();

        $this->assertTrue($listing->fresh()->isApproved());
    }

    public function test_expired_listing_can_still_be_taken_down(): void
    {
        $listing = Listing::factory()->expired()->create();
        $this->reportFor($listing);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listing-reports/{$listing->id}/take-down", ['reason' => 'Scam'])
            ->assertSessionHas('success');

        $this->assertTrue(Listing::withTrashed()->findOrFail($listing->id)->isRejected());
    }

    public function test_listing_service_take_down_throws_for_a_pending_listing(): void
    {
        $listing = Listing::factory()->pendingReview()->create();

        $this->expectException(InvalidListingStatusTransitionException::class);

        app(ListingService::class)->takeDown($listing, 'Scam');
    }

    public function test_failed_take_down_rolls_back_and_sends_no_email(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->pendingReview()->create();
        $this->reportFor($listing);

        try {
            app(ListingReportService::class)->takeDown($listing, $admin, 'Scam');
            $this->fail('Expected the take-down to be refused.');
        } catch (InvalidListingStatusTransitionException) {
        }

        $this->assertSame(1, $listing->openReports()->count());
        $this->assertTrue($listing->fresh()->isPendingReview());
        Notification::assertNothingSent();
    }

    public function test_reject_still_requires_a_pending_listing(): void
    {
        $this->expectException(InvalidListingStatusTransitionException::class);

        app(ListingService::class)->reject(Listing::factory()->approved()->create(), 'Nope');
    }

    public function test_taken_down_listing_is_purged_with_its_reports(): void
    {
        Notification::fake();

        $listing = Listing::factory()->approved()->create();
        $report = $this->reportFor($listing);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/listing-reports/{$listing->id}/take-down", ['reason' => 'Scam']);

        $this->travel(8)->days();

        $this->artisan('listings:purge-rejected')->assertSuccessful();

        $this->assertNull(Listing::withTrashed()->find($listing->id));
        $this->assertNull(ListingReport::find($report->id));
    }

    // ---- Dashboard, navigation, review page -----------------------------

    public function test_dashboard_counts_listings_with_open_reports(): void
    {
        $admin = User::factory()->admin()->create();

        $reported = Listing::factory()->approved()->create();
        $this->reportFor($reported);
        $this->reportFor($reported);

        $second = Listing::factory()->approved()->create();
        $this->reportFor($second);

        ListingReport::factory()->resolved()->create();

        $ownerDeleted = Listing::factory()->approved()->create();
        $this->reportFor($ownerDeleted);
        $ownerDeleted->delete();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page->where('stats.reportedListings', 2));
    }

    public function test_admin_nav_includes_reported_listings(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/listing-reports')
            ->assertInertia(fn (Assert $page) => $page
                ->where('adminNav.reported_listings', __('app.admin.nav.reported_listings'))
            );
    }

    public function test_review_page_shows_open_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->approved()->create();
        $this->reportFor($listing);

        $this->actingAs($admin)
            ->get("/admin/listings/{$listing->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('reportModeration.id', $listing->id)
                ->where('reportModeration.openReportsCount', 1)
                ->where('reportModeration.canTakeDown', true)
                ->where('reportTranslations.panel_heading', __('app.admin.listing_reports.panel_heading'))
            );

        $clean = Listing::factory()->approved()->create();

        $this->actingAs($admin)
            ->get("/admin/listings/{$clean->id}")
            ->assertInertia(fn (Assert $page) => $page->where('reportModeration', null));
    }
}
