<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\SavedSearchDigestNotification;
use App\Services\SavedSearchService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SavedSearchDigestTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(7, 0));
    }

    private function search(User $user, array $filters = ['listingType' => 'rent', 'city' => 'Belgrade'], array $attributes = []): SavedSearch
    {
        return SavedSearch::factory()->for($user)->withFilters($filters)->create([
            'last_alerted_at' => now(),
            ...$attributes,
        ]);
    }

    private function listing(array $attributes = []): Listing
    {
        return Listing::factory()->approved()->create([
            'listing_type' => 'rent',
            'city' => 'Belgrade',
            'approved_at' => now(),
            'expires_at' => now()->addDays(90),
            ...$attributes,
        ]);
    }

    private function runDigest(string $expectedOutput): void
    {
        $this->artisan('saved-searches:send-digest')
            ->expectsOutput($expectedOutput)
            ->assertSuccessful();
    }

    /**
     * @return list<array{name: string, url: string, listings: list<array{title: string, url: string, price: string, city: string}>, total: int}>
     */
    private function sentSections(User $user): array
    {
        $sections = null;

        Notification::assertSentTo($user, SavedSearchDigestNotification::class, function (SavedSearchDigestNotification $notification) use (&$sections): bool {
            $sections = $notification->sections;

            return true;
        });

        return $sections;
    }

    // ---- Matching --------------------------------------------------------

    public function test_listing_approved_after_saving_that_matches_is_emailed(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = $this->search($user, attributes: ['name' => 'Belgrade rentals']);

        $this->travel(2)->hours();
        $listing = $this->listing(['title' => 'Sunny Loft', 'price' => 285000]);
        $this->travel(1)->day();

        $this->runDigest('Sent 1 digests.');

        Notification::assertSentToTimes($user, SavedSearchDigestNotification::class, 1);
        $this->assertSame([[
            'name' => 'Belgrade rentals',
            'url' => $search->listingsUrl(),
            'listings' => [[
                'title' => 'Sunny Loft',
                'url' => route('listings.show', $listing->slug),
                'price' => '€285,000',
                'city' => 'Belgrade',
            ]],
            'total' => 1,
        ]], $this->sentSections($user));
    }

    public function test_listings_that_should_not_alert_are_excluded(): void
    {
        $user = User::factory()->regularUser()->create();
        $this->search($user);

        $this->listing(['approved_at' => now()->subMinute()]);

        $this->travel(1)->hour();
        $this->listing(['listing_type' => 'sale']);
        $this->listing(['city' => 'Novi Sad']);
        $this->listing(['expires_at' => now()->addMinutes(30)]);
        Listing::factory()->rented()->create(['city' => 'Belgrade', 'approved_at' => now(), 'closed_at' => now()]);
        Listing::factory()->pendingReview()->create(['listing_type' => 'rent', 'city' => 'Belgrade']);
        $this->listing(['user_id' => User::factory()->suspended()]);

        $this->travel(1)->day();

        $this->runDigest('Sent 0 digests.');

        Notification::assertNothingSent();
    }

    public function test_users_own_listings_are_excluded(): void
    {
        $user = User::factory()->regularUser()->create();
        $this->search($user);

        $this->travel(1)->hour();
        $this->listing(['user_id' => $user->id]);
        $this->travel(1)->day();

        $this->runDigest('Sent 0 digests.');

        Notification::assertNothingSent();
    }

    public function test_agency_owners_own_agency_listings_are_excluded(): void
    {
        $agencyOwner = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $agencyOwner->id]);
        $otherAgency = Agency::factory()->approved()->create();
        $this->search($agencyOwner);

        $this->travel(1)->hour();
        $this->listing(['user_id' => null, 'agency_id' => $agency->id, 'title' => 'Our own']);
        $this->listing(['user_id' => null, 'agency_id' => $otherAgency->id, 'title' => 'Competitor']);
        $this->listing(['title' => 'Private seller']);
        $this->travel(1)->day();

        $this->runDigest('Sent 1 digests.');

        $this->assertEqualsCanonicalizing(
            ['Competitor', 'Private seller'],
            array_column($this->sentSections($agencyOwner)[0]['listings'], 'title'),
        );
    }

    public function test_listing_approved_while_a_run_is_in_progress_waits_for_the_next_run(): void
    {
        $user = User::factory()->regularUser()->create();
        $this->search($user);

        $this->travel(1)->day();
        $runStartedAt = now()->toImmutable();
        $this->travel(5)->minutes();
        $this->listing(['approved_at' => now()->subMinute()]);

        $sent = app(SavedSearchService::class)->sendDigest($user->load('agency', 'savedSearches'), $runStartedAt);

        $this->assertFalse($sent);
        Notification::assertNothingSent();

        $this->travel(1)->day();
        $this->runDigest('Sent 1 digests.');
        Notification::assertSentToTimes($user, SavedSearchDigestNotification::class, 1);
    }

    public function test_several_matching_searches_are_combined_into_one_email(): void
    {
        $user = User::factory()->regularUser()->create();
        $this->search($user, ['listingType' => 'rent'], ['name' => 'Rentals']);
        $this->search($user, ['city' => 'Belgrade'], ['name' => 'Belgrade']);
        $this->search($user, ['city' => 'Nowhere'], ['name' => 'Nowhere']);

        $this->travel(1)->hour();
        $this->listing();
        $this->travel(1)->day();

        $this->runDigest('Sent 1 digests.');

        Notification::assertSentToTimes($user, SavedSearchDigestNotification::class, 1);
        $this->assertSame(['Rentals', 'Belgrade'], array_column($this->sentSections($user), 'name'));
    }

    public function test_each_search_lists_at_most_ten_listings_with_the_total(): void
    {
        $user = User::factory()->regularUser()->create();
        $this->search($user);

        foreach (range(1, 12) as $minute) {
            $this->travel(1)->minute();
            $this->listing(['title' => "Listing {$minute}"]);
        }

        $this->travel(1)->day();
        $this->runDigest('Sent 1 digests.');

        $section = $this->sentSections($user)[0];
        $this->assertSame(12, $section['total']);
        $this->assertCount(10, $section['listings']);
        $this->assertSame('Listing 12', $section['listings'][0]['title']);
        $this->assertSame('Listing 3', $section['listings'][9]['title']);
    }

    // ---- Marks and repeats -----------------------------------------------

    public function test_marks_advance_to_the_run_start_so_nothing_is_sent_twice(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = $this->search($user);
        $idle = $this->search($user, ['city' => 'Nowhere']);

        $this->travel(1)->hour();
        $this->listing();
        $this->travel(1)->day();
        $runStartedAt = now()->toImmutable();

        $this->runDigest('Sent 1 digests.');

        $this->assertTrue($search->fresh()->last_alerted_at->equalTo($runStartedAt));
        $this->assertTrue($idle->fresh()->last_alerted_at->equalTo($runStartedAt));

        $this->travel(1)->day();
        $this->runDigest('Sent 0 digests.');

        Notification::assertSentToTimes($user, SavedSearchDigestNotification::class, 1);
    }

    public function test_marks_advance_even_when_nothing_matched(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = $this->search($user);

        $this->travel(1)->day();
        $this->runDigest('Sent 0 digests.');

        Notification::assertNothingSent();
        $this->assertTrue($search->fresh()->last_alerted_at->equalTo(now()));
    }

    public function test_alerts_off_searches_are_skipped_and_their_marks_kept(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = $this->search($user, attributes: ['alerts_enabled' => false]);
        $mark = $search->last_alerted_at;

        $this->travel(1)->hour();
        $this->listing();
        $this->travel(1)->day();

        $this->runDigest('Sent 0 digests.');

        Notification::assertNothingSent();
        $this->assertTrue($search->fresh()->last_alerted_at->equalTo($mark));
    }

    public function test_suspended_users_and_admins_receive_nothing(): void
    {
        $suspended = User::factory()->suspended()->create();
        $admin = User::factory()->admin()->create();
        $this->search($suspended);
        $this->search($admin);

        $this->travel(1)->hour();
        $this->listing();
        $this->travel(1)->day();

        $this->runDigest('Sent 0 digests.');

        Notification::assertNothingSent();
    }

    public function test_re_enabled_search_does_not_back_fill_the_paused_period(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = $this->search($user, attributes: ['alerts_enabled' => false]);

        $this->travel(1)->hour();
        $this->listing(['title' => 'Missed while paused']);
        $this->travel(1)->hour();

        $this->actingAs($user)->patch("/saved-searches/{$search->id}", ['alerts_enabled' => true]);

        $this->travel(1)->hour();
        $this->listing(['title' => 'After re-enabling']);
        $this->travel(1)->day();

        $this->runDigest('Sent 1 digests.');

        $this->assertSame(['After re-enabling'], array_column($this->sentSections($user)[0]['listings'], 'title'));
    }

    // ---- Stale stored filters ---------------------------------------------

    public function test_invalid_stored_filter_keys_are_ignored(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = $this->search($user);
        $search->forceFill(['filters' => [
            'listingType' => 'rent',
            'propertyType' => 'spaceship',
            'minPrice' => 'cheap',
            'minBedrooms' => ['2'],
            'amenities' => ['pool', 'jacuzzi'],
            'retiredFilter' => 'yes',
        ]])->save();

        $this->travel(1)->hour();
        $this->listing(['title' => 'With pool', 'amenities' => ['pool'], 'bedrooms' => 1]);
        $this->listing(['title' => 'Without pool', 'amenities' => []]);
        $this->travel(1)->day();

        $this->runDigest('Sent 1 digests.');

        $this->assertSame(['With pool'], array_column($this->sentSections($user)[0]['listings'], 'title'));
    }

    public function test_search_left_with_no_valid_filters_is_not_sent_every_listing(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = $this->search($user);
        $search->forceFill(['filters' => ['propertyType' => 'spaceship', 'amenities' => ['jacuzzi']]])->save();

        $this->travel(1)->hour();
        $this->listing();
        $this->travel(1)->day();

        $this->runDigest('Sent 0 digests.');

        Notification::assertNothingSent();
        $this->assertTrue($search->fresh()->last_alerted_at->equalTo(now()));
    }

    public function test_search_saved_before_a_filter_key_existed_still_runs(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = $this->search($user);
        $search->forceFill(['filters' => ['listingType' => 'rent']])->save();

        $this->travel(1)->hour();
        $this->listing();
        $this->travel(1)->day();

        $this->runDigest('Sent 1 digests.');
    }

    // ---- Email ------------------------------------------------------------

    public function test_digest_email_renders_links_prices_and_unsubscribe(): void
    {
        $user = User::factory()->regularUser()->create();
        $this->search($user, attributes: ['name' => 'Belgrade rentals']);

        foreach (range(1, 11) as $minute) {
            $this->travel(1)->minute();
            $this->listing(['title' => "Loft {$minute}", 'price' => 1850]);
        }

        $this->travel(1)->day();
        $this->runDigest('Sent 1 digests.');

        $html = null;
        Notification::assertSentTo($user, SavedSearchDigestNotification::class, function (SavedSearchDigestNotification $notification) use ($user, &$html): bool {
            $mail = $notification->toMail($user);

            $this->assertSame(__('app.emails.saved_search_digest.subject'), $mail->subject);
            $this->assertSame(route('saved-searches.index'), $mail->actionUrl);
            $this->assertTrue(URL::hasValidSignature(request()->create($notification->unsubscribeUrl)));
            $this->assertStringContainsString("/saved-searches/unsubscribe/{$user->id}", $notification->unsubscribeUrl);

            $html = (string) $mail->render();

            return true;
        });

        $this->assertStringContainsString('Belgrade rentals', $html);
        $this->assertStringContainsString('Loft 11', $html);
        $this->assertStringContainsString('€1,850', $html);
        $this->assertStringContainsString('…and 1 more listing.', $html);
        $this->assertStringContainsString(__('app.emails.saved_search_digest.unsubscribe'), $html);
    }

    public function test_punctuation_in_titles_renders_cleanly_in_both_email_parts(): void
    {
        $notification = new SavedSearchDigestNotification(
            [[
                'name' => 'St. Mary - *Lofts* #1',
                'url' => 'https://example.test/listings?listingType=rent',
                'listings' => [[
                    'title' => "St. Mary's - Loft (new!) [top_floor]",
                    'url' => 'https://example.test/listings/loft',
                    'price' => '€1,850',
                    'city' => 'Novi Sad',
                ]],
                'total' => 1,
            ]],
            'https://example.test/saved-searches',
            'https://example.test/unsubscribe',
        );
        $mail = $notification->toMail(User::factory()->make());

        $html = (string) $mail->render();
        $text = app(Markdown::class)->renderText('notifications::email', $mail->data())->toHtml();

        $this->assertStringContainsString('St. Mary - *Lofts* #1</strong>', $html);
        $this->assertStringContainsString("St. Mary's - Loft (new!) [top_floor]</a> — €1,850 — Novi Sad", html_entity_decode($html, ENT_QUOTES));
        $this->assertStringContainsString("St. Mary's - Loft (new!)", $text);
        $this->assertStringNotContainsString('St\.', $text);
        $this->assertStringNotContainsString('\-', $text);
    }

    // ---- Unsubscribe -------------------------------------------------------

    public function test_signed_unsubscribe_link_only_shows_a_confirm_page(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = $this->search($user);

        $this->get(URL::signedRoute('saved-searches.unsubscribe.show', ['user' => $user->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SavedSearches/Unsubscribe')
                ->where('confirmUrl', URL::signedRoute('saved-searches.unsubscribe', ['user' => $user->id]))
                ->where('translations.title', __('app.saved_searches.unsubscribe.title'))
            );

        $this->assertTrue($search->fresh()->alerts_enabled);
    }

    public function test_signed_unsubscribe_post_turns_off_all_the_users_alerts(): void
    {
        $user = User::factory()->regularUser()->create();
        $first = $this->search($user);
        $second = $this->search($user, ['city' => 'Novi Sad']);
        $other = $this->search(User::factory()->regularUser()->create());

        $this->post(URL::signedRoute('saved-searches.unsubscribe', ['user' => $user->id]))
            ->assertRedirect(URL::signedRoute('saved-searches.unsubscribe.show', ['user' => $user->id]))
            ->assertSessionHas('success', __('app.saved_searches.unsubscribe.done'));

        $this->assertFalse($first->fresh()->alerts_enabled);
        $this->assertFalse($second->fresh()->alerts_enabled);
        $this->assertTrue($other->fresh()->alerts_enabled);
        $this->assertDatabaseCount('saved_searches', 3);
    }

    public function test_tampered_or_missing_signatures_are_rejected(): void
    {
        $user = User::factory()->regularUser()->create();
        $victim = User::factory()->regularUser()->create();
        $search = $this->search($victim);

        $tamperedShow = str_replace("/unsubscribe/{$user->id}", "/unsubscribe/{$victim->id}", URL::signedRoute('saved-searches.unsubscribe.show', ['user' => $user->id]));
        $tamperedPost = str_replace("/unsubscribe/{$user->id}", "/unsubscribe/{$victim->id}", URL::signedRoute('saved-searches.unsubscribe', ['user' => $user->id]));

        $this->get($tamperedShow)->assertForbidden();
        $this->post($tamperedPost)->assertForbidden();
        $this->get("/saved-searches/unsubscribe/{$victim->id}")->assertForbidden();
        $this->post("/saved-searches/unsubscribe/{$victim->id}")->assertForbidden();

        $this->assertTrue($search->fresh()->alerts_enabled);
    }

    // ---- Schedule -----------------------------------------------------------

    public function test_digest_is_scheduled_daily_at_seven_belgrade_time(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event) => str_contains((string) $event->command, 'saved-searches:send-digest'));

        $this->assertNotNull($event);
        $this->assertSame('0 7 * * *', $event->expression);
        $this->assertSame('Europe/Belgrade', $event->timezone);
    }
}
