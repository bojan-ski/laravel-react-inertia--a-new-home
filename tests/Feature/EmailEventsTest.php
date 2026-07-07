<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\ReportedChatMessage;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use App\Notifications\AgencyApprovedNotification;
use App\Notifications\AgencyRejectedNotification;
use App\Notifications\ListingApprovedNotification;
use App\Notifications\ListingRejectedNotification;
use App\Notifications\UserSuspendedNotification;
use App\Services\MailNotificationService;
use App\Services\UserAccountService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use ReflectionClass;
use Tests\TestCase;

class EmailEventsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // --- Listing events -------------------------------------------------

    public function test_approving_a_listing_notifies_the_regular_user_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($owner, 'owner')->create();

        $this->actingAs($this->admin())
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertRedirect();

        Notification::assertSentTo($owner, ListingApprovedNotification::class);
        Notification::assertSentTimes(ListingApprovedNotification::class, 1);
    }

    public function test_rejecting_a_listing_notifies_the_regular_user_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($owner, 'owner')->create();

        $this->actingAs($this->admin())
            ->post("/admin/listings/{$listing->id}/reject", ['reason' => 'Incomplete details.'])
            ->assertRedirect();

        Notification::assertSentTo($owner, ListingRejectedNotification::class);
        Notification::assertSentTimes(ListingRejectedNotification::class, 1);
    }

    public function test_approving_an_agency_listing_notifies_the_agency_owner(): void
    {
        Notification::fake();

        $agency = Agency::factory()->approved()->create();
        $listing = Listing::factory()->pendingReview()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($this->admin())
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertRedirect();

        Notification::assertSentTo($agency->user, ListingApprovedNotification::class);
        Notification::assertSentTimes(ListingApprovedNotification::class, 1);
    }

    public function test_failed_listing_approval_dispatches_no_mail(): void
    {
        Notification::fake();

        $listing = Listing::factory()->approved()->create();

        $this->actingAs($this->admin())
            ->post("/admin/listings/{$listing->id}/approve")
            ->assertForbidden();

        Notification::assertNothingSent();
    }

    // --- Agency events --------------------------------------------------

    public function test_approving_an_agency_notifies_the_agency_owner(): void
    {
        Notification::fake();

        $agency = Agency::factory()->pendingVerification()->create();

        $this->actingAs($this->admin())
            ->post("/admin/agencies/{$agency->id}/approve")
            ->assertRedirect();

        Notification::assertSentTo($agency->user, AgencyApprovedNotification::class);
        Notification::assertSentTimes(AgencyApprovedNotification::class, 1);
    }

    public function test_rejecting_an_agency_notifies_the_owner_before_hard_delete(): void
    {
        Notification::fake();

        $agency = Agency::factory()->pendingVerification()->create();
        $owner = $agency->user;

        $this->actingAs($this->admin())
            ->post("/admin/agencies/{$agency->id}/reject", ['reason' => 'Spam agency.'])
            ->assertRedirect();

        Notification::assertSentTo($owner, AgencyRejectedNotification::class);
        Notification::assertSentTimes(AgencyRejectedNotification::class, 1);
        $this->assertDatabaseMissing('agencies', ['id' => $agency->id]);
    }

    public function test_failed_agency_approval_dispatches_no_mail(): void
    {
        Notification::fake();

        $agency = Agency::factory()->approved()->create();

        $this->actingAs($this->admin())
            ->post("/admin/agencies/{$agency->id}/approve")
            ->assertForbidden();

        Notification::assertNothingSent();
    }

    // --- User events ----------------------------------------------------

    public function test_suspending_a_user_notifies_the_user(): void
    {
        Notification::fake();

        $offender = User::factory()->regularUser()->create();

        $this->actingAs($this->admin())
            ->post("/admin/users/{$offender->id}/suspend")
            ->assertRedirect();

        Notification::assertSentTo($offender, UserSuspendedNotification::class);
        Notification::assertSentTimes(UserSuspendedNotification::class, 1);
    }

    public function test_suspending_an_already_suspended_user_does_not_redispatch(): void
    {
        Notification::fake();

        $offender = User::factory()->regularUser()->suspended()->create();

        app(UserAccountService::class)->suspend($offender);

        Notification::assertNothingSent();
    }

    public function test_report_flow_suspension_produces_exactly_one_email(): void
    {
        Notification::fake();

        $offender = User::factory()->regularUser()->create();
        $report = ReportedChatMessage::factory()->create(['offending_user_id' => $offender->id]);

        $this->actingAs($this->admin())
            ->post("/admin/reported-messages/{$report->id}/suspend")
            ->assertRedirect();

        Notification::assertSentTo($offender, UserSuspendedNotification::class);
        Notification::assertSentTimes(UserSuspendedNotification::class, 1);
    }

    public function test_deleting_a_user_notifies_the_deleted_user_before_deletion(): void
    {
        Notification::fake();

        $user = User::factory()->regularUser()->create(['email' => 'gone@example.test']);

        $this->actingAs($this->admin())
            ->delete("/admin/users/{$user->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        Notification::assertSentOnDemand(
            AccountDeletedNotification::class,
            fn (AccountDeletedNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => ($notifiable->routes['mail'] ?? null) === 'gone@example.test'
        );
        Notification::assertSentTimes(AccountDeletedNotification::class, 1);
    }

    // --- Centralization & password reset -------------------------------

    public function test_mail_notification_service_has_no_send_password_reset_method(): void
    {
        $this->assertFalse(
            method_exists(MailNotificationService::class, 'sendPasswordReset'),
            'Password reset is the framework\'s territory; MailNotificationService must not own it.'
        );
    }

    public function test_password_reset_email_pipeline_still_works(): void
    {
        Notification::fake();

        $user = User::factory()->regularUser()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_mail_notification_service_is_the_only_dispatcher(): void
    {
        $offenders = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            if (str_contains(file_get_contents($file->getPathname()), 'Mail::to(')) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders, 'No app file should dispatch mail via Mail::to(); all mail routes through MailNotificationService.');
    }

    public function test_all_six_notifications_render_a_mail_message(): void
    {
        $notifications = [
            ListingApprovedNotification::class,
            ListingRejectedNotification::class,
            AgencyApprovedNotification::class,
            AgencyRejectedNotification::class,
            UserSuspendedNotification::class,
            AccountDeletedNotification::class,
        ];

        foreach ($notifications as $notification) {
            $this->assertTrue(
                (new ReflectionClass($notification))->hasMethod('toMail'),
                "{$notification} must render a mail message."
            );
        }
    }
}
