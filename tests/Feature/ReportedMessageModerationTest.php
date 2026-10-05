<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ReportedChatMessage;
use App\Models\User;
use App\Notifications\UserSuspendedNotification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportedMessageModerationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function conversationBetween(User $buyer, User $owner): Conversation
    {
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        return Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);
    }

    private function messageFrom(Conversation $conversation, User $sender, string $body = 'Hello there'): ChatMessage
    {
        return ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);
    }

    // ---- Reporting ------------------------------------------------------

    public function test_participant_can_report_another_participants_message(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationBetween($buyer, $owner);
        $message = $this->messageFrom($conversation, $owner, 'Rude message');

        $this->actingAs($buyer)
            ->post("/messages/{$message->id}/report", ['reason' => 'This is abusive.'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Message reported.');

        $report = ReportedChatMessage::firstOrFail();

        $this->assertSame($message->id, $report->chat_message_id);
        $this->assertSame($buyer->id, $report->reported_by_user_id);
        $this->assertSame($owner->id, $report->offending_user_id);
        $this->assertSame('This is abusive.', $report->reason);
        $this->assertNull($report->resolved_at);
        $this->assertNull($report->resolved_by_user_id);
    }

    public function test_cannot_report_own_message(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationBetween($buyer, $owner);
        $message = $this->messageFrom($conversation, $buyer);

        $this->actingAs($buyer)
            ->post("/messages/{$message->id}/report", ['reason' => 'trying to report myself'])
            ->assertRedirect()
            ->assertSessionHas('error', 'You cannot report your own message.');

        $this->assertSame(0, ReportedChatMessage::count());
    }

    public function test_non_participant_cannot_report(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $outsider = User::factory()->regularUser()->create();
        $conversation = $this->conversationBetween($buyer, $owner);
        $message = $this->messageFrom($conversation, $owner);

        $this->actingAs($outsider)
            ->post("/messages/{$message->id}/report", ['reason' => 'butting in'])
            ->assertRedirect()
            ->assertSessionHas('error', 'You can only report messages in conversations you participate in.');

        $this->assertSame(0, ReportedChatMessage::count());
    }

    public function test_duplicate_report_is_rejected_by_the_service(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationBetween($buyer, $owner);
        $message = $this->messageFrom($conversation, $owner);

        $this->actingAs($buyer)->post("/messages/{$message->id}/report", ['reason' => 'first']);

        $this->actingAs($buyer)
            ->post("/messages/{$message->id}/report", ['reason' => 'second'])
            ->assertRedirect()
            ->assertSessionHas('error', 'You have already reported this message.');

        $this->assertSame(1, ReportedChatMessage::count());
    }

    public function test_database_unique_constraint_prevents_duplicates(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationBetween($buyer, $owner);
        $message = $this->messageFrom($conversation, $owner);

        ReportedChatMessage::factory()->create([
            'chat_message_id' => $message->id,
            'reported_by_user_id' => $buyer->id,
            'offending_user_id' => $owner->id,
            'reason' => 'first',
        ]);

        $this->expectException(QueryException::class);

        ReportedChatMessage::factory()->create([
            'chat_message_id' => $message->id,
            'reported_by_user_id' => $buyer->id,
            'offending_user_id' => $owner->id,
            'reason' => 'second',
        ]);
    }

    public function test_reason_is_required(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationBetween($buyer, $owner);
        $message = $this->messageFrom($conversation, $owner);

        $this->actingAs($buyer)
            ->post("/messages/{$message->id}/report", ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(0, ReportedChatMessage::count());
    }

    public function test_suspended_user_cannot_report(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->suspended()->create();
        $conversation = $this->conversationBetween($buyer, $owner);
        $message = $this->messageFrom($conversation, $owner);

        $this->actingAs($buyer)
            ->post("/messages/{$message->id}/report", ['reason' => 'reporting while suspended'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Your account is suspended and cannot report messages.');

        $this->assertSame(0, ReportedChatMessage::count());
    }

    public function test_guest_cannot_report(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationBetween($buyer, $owner);
        $message = $this->messageFrom($conversation, $owner);

        $this->post("/messages/{$message->id}/report", ['reason' => 'x'])
            ->assertRedirect('/login');
    }

    // ---- Admin access ---------------------------------------------------

    public function test_admin_can_view_the_moderation_queue(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/reported-messages')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/ReportedMessages/Index'));
    }

    public function test_guest_is_redirected_from_admin_page(): void
    {
        $this->get('/admin/reported-messages')->assertRedirect('/login');
    }

    public function test_regular_user_gets_403_on_admin_page(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/admin/reported-messages')
            ->assertForbidden();
    }

    public function test_agency_user_gets_403_on_admin_page(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->get('/admin/reported-messages')
            ->assertForbidden();
    }

    public function test_only_unresolved_reports_are_listed(): void
    {
        $unresolved = ReportedChatMessage::factory()->create();
        ReportedChatMessage::factory()->resolved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/reported-messages')
            ->assertInertia(fn (Assert $page) => $page
                ->has('reports.data', 1)
                ->where('reports.data.0.id', $unresolved->id)
            );
    }

    public function test_reports_are_ordered_newest_first(): void
    {
        $older = ReportedChatMessage::factory()->create(['created_at' => now()->subDay()]);
        $newer = ReportedChatMessage::factory()->create(['created_at' => now()]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/reported-messages')
            ->assertInertia(fn (Assert $page) => $page
                ->where('reports.data.0.id', $newer->id)
                ->where('reports.data.1.id', $older->id)
            );
    }

    public function test_moderation_payload_has_the_documented_shape(): void
    {
        $owner = User::factory()->regularUser()->create(['name' => 'Olivia Owner']);
        $buyer = User::factory()->regularUser()->create(['name' => 'Bruno Buyer']);
        $conversation = $this->conversationBetween($buyer, $owner);
        $message = $this->messageFrom($conversation, $owner, 'The reported body');

        $report = ReportedChatMessage::factory()->create([
            'chat_message_id' => $message->id,
            'reported_by_user_id' => $buyer->id,
            'offending_user_id' => $owner->id,
            'reason' => 'Because reasons',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/reported-messages')
            ->assertInertia(fn (Assert $page) => $page
                ->where('reports.data.0.id', $report->id)
                ->where('reports.data.0.reason', 'Because reasons')
                ->where('reports.data.0.listingTitle', $conversation->listing->title)
                ->where('reports.data.0.conversationId', $conversation->id)
                ->where('reports.data.0.messageBody', 'The reported body')
                ->where('reports.data.0.messageSenderName', 'Olivia Owner')
                ->where('reports.data.0.reportedByName', 'Bruno Buyer')
                ->where('reports.data.0.offendingUserName', 'Olivia Owner')
            );
    }

    // ---- Resolve --------------------------------------------------------

    public function test_admin_can_resolve_a_report(): void
    {
        $admin = User::factory()->admin()->create();
        $report = ReportedChatMessage::factory()->create();

        $this->actingAs($admin)
            ->post("/admin/reported-messages/{$report->id}/resolve")
            ->assertRedirect()
            ->assertSessionHas('success', 'Report resolved.');

        $report->refresh();

        $this->assertNotNull($report->resolved_at);
        $this->assertSame($admin->id, $report->resolved_by_user_id);
    }

    public function test_resolved_report_cannot_be_resolved_again(): void
    {
        $report = ReportedChatMessage::factory()->resolved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/reported-messages/{$report->id}/resolve")
            ->assertForbidden();
    }

    // ---- Suspend --------------------------------------------------------

    public function test_admin_can_suspend_the_offending_user(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $offender = User::factory()->regularUser()->create();
        $report = ReportedChatMessage::factory()->create(['offending_user_id' => $offender->id]);

        $this->actingAs($admin)
            ->post("/admin/reported-messages/{$report->id}/suspend")
            ->assertRedirect()
            ->assertSessionHas('success', 'User suspended and report resolved.');

        $offender->refresh();
        $report->refresh();

        $this->assertSame(UserStatus::Suspended, $offender->status);
        $this->assertNotNull($offender->suspended_at);
        $this->assertNotNull($report->resolved_at);
        $this->assertSame($admin->id, $report->resolved_by_user_id);
    }

    public function test_suspend_delegates_to_the_user_account_service_and_sends_the_email(): void
    {
        Notification::fake();

        $offender = User::factory()->regularUser()->create();
        $report = ReportedChatMessage::factory()->create(['offending_user_id' => $offender->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/reported-messages/{$report->id}/suspend");

        Notification::assertSentTo($offender, UserSuspendedNotification::class);
    }

    public function test_suspend_skips_suspension_when_offender_already_suspended(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $offender = User::factory()->regularUser()->suspended()->create();
        $report = ReportedChatMessage::factory()->create(['offending_user_id' => $offender->id]);

        $this->actingAs($admin)
            ->post("/admin/reported-messages/{$report->id}/suspend")
            ->assertRedirect()
            ->assertSessionHas('success', 'User suspended and report resolved.');

        $report->refresh();

        $this->assertNotNull($report->resolved_at);
        Notification::assertNothingSent();
    }

    public function test_resolved_report_cannot_trigger_a_suspend(): void
    {
        $report = ReportedChatMessage::factory()->resolved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/reported-messages/{$report->id}/suspend")
            ->assertForbidden();
    }

    public function test_regular_user_cannot_resolve_or_suspend(): void
    {
        $report = ReportedChatMessage::factory()->create();
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post("/admin/reported-messages/{$report->id}/resolve")
            ->assertForbidden();

        $this->actingAs($user)
            ->post("/admin/reported-messages/{$report->id}/suspend")
            ->assertForbidden();
    }

    public function test_the_reported_messages_query_count_does_not_grow_with_reports(): void
    {
        $admin = User::factory()->admin()->create();

        $countQueries = function () use ($admin): int {
            $actor = $admin->fresh();
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($actor)->get('/admin/reported-messages')->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $seed = function (): void {
            ReportedChatMessage::factory()->create();
        };

        $seed();
        $seed();
        $withTwo = $countQueries();

        foreach (range(1, 4) as $index) {
            $seed();
        }

        $this->assertSame(6, ReportedChatMessage::count());
        $this->assertSame($withTwo, $countQueries());
    }
}
