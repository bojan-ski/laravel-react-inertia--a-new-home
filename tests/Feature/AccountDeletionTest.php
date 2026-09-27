<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use App\Services\UserAccountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    /**
     * @return array{Conversation, User, User}
     */
    private function conversationBetweenOwnerAndBuyer(): array
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create(['title' => 'Sunny Loft']);

        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'listing_title' => 'Sunny Loft',
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $buyer->id, 'body' => 'Is it available?']);
        ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $owner->id, 'body' => 'Yes it is.']);

        return [$conversation, $owner, $buyer];
    }

    public function test_user_can_delete_their_account_with_the_correct_password(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password', 'email' => 'leaving@example.test']);

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/')
            ->assertSessionHas('success', __('app.profile_settings.delete.flash'));

        $this->assertGuest();
        $this->assertModelMissing($user);
        Notification::assertSentOnDemand(
            AccountDeletedNotification::class,
            fn ($notification, array $channels, $notifiable): bool => $notifiable->routes['mail'] === 'leaving@example.test',
        );
    }

    public function test_wrong_password_keeps_the_account(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password']);

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'not-my-password'])
            ->assertSessionHasErrors('password');

        $this->assertAuthenticatedAs($user);
        $this->assertModelExists($user);
        Notification::assertNothingSent();
    }

    public function test_password_is_required(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->delete('/profile')
            ->assertSessionHasErrors('password');

        $this->assertModelExists($user);
    }

    public function test_listings_and_bookmarks_are_deleted_with_the_account(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password']);
        $listing = Listing::factory()->approved()->for($user, 'owner')->create();
        $bookmark = Bookmark::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete('/profile', ['password' => 'password']);

        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertModelMissing($bookmark);
    }

    public function test_buyer_deletion_keeps_the_thread_for_the_owner_as_deleted_user(): void
    {
        [$conversation, $owner, $buyer] = $this->conversationBetweenOwnerAndBuyer();

        app(UserAccountService::class)->delete($buyer);

        $conversation->refresh();
        $this->assertNull($conversation->buyer_id);
        $this->assertSame(2, $conversation->messages()->count());
        $this->assertDatabaseHas('chat_messages', ['conversation_id' => $conversation->id, 'sender_id' => null, 'body' => 'Is it available?']);

        $this->actingAs($owner)
            ->get("/conversations/{$conversation->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversation.otherParticipant.id', null)
                ->where('conversation.otherParticipant.name', __('app.conversations.deleted_user'))
                ->where('conversation.messages.0.senderId', null)
                ->where('conversation.messages.0.senderName', __('app.conversations.deleted_user'))
                ->where('canReply', false));
    }

    public function test_owner_deletion_keeps_the_thread_for_the_buyer_with_the_listing_title(): void
    {
        [$conversation, $owner, $buyer] = $this->conversationBetweenOwnerAndBuyer();

        app(UserAccountService::class)->delete($owner);

        $conversation->refresh();
        $this->assertNull($conversation->owner_id);
        $this->assertNull($conversation->listing_id);

        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page
                ->has('conversations', 1)
                ->where('conversations.0.listing.title', 'Sunny Loft')
                ->where('conversations.0.listing.slug', null)
                ->where('conversations.0.otherParticipant.name', __('app.conversations.deleted_user')));

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversation.listing.title', 'Sunny Loft')
                ->where('canReply', false));
    }

    public function test_remaining_participant_cannot_send_messages_to_a_deleted_user(): void
    {
        [$conversation, $owner, $buyer] = $this->conversationBetweenOwnerAndBuyer();

        app(UserAccountService::class)->delete($buyer);

        $this->actingAs($owner)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Hello?'])
            ->assertForbidden();

        $this->assertSame(2, $conversation->messages()->count());
    }

    public function test_conversation_is_purged_once_both_participants_are_gone(): void
    {
        [$conversation, $owner, $buyer] = $this->conversationBetweenOwnerAndBuyer();
        $service = app(UserAccountService::class);

        $service->delete($buyer);
        $this->assertModelExists($conversation);

        $service->delete($owner);
        $this->assertModelMissing($conversation);
        $this->assertDatabaseMissing('chat_messages', ['conversation_id' => $conversation->id]);
    }

    public function test_messages_from_a_deleted_sender_cannot_be_reported(): void
    {
        [$conversation, $owner, $buyer] = $this->conversationBetweenOwnerAndBuyer();
        $message = $conversation->messages()->where('sender_id', $buyer->id)->first();

        app(UserAccountService::class)->delete($buyer);

        $this->actingAs($owner)
            ->post("/messages/{$message->id}/report", ['reason' => 'Spam'])
            ->assertSessionHas('error', __('app.reports.errors.sender_deleted'));

        $this->assertDatabaseCount('reported_chat_messages', 0);
    }

    public function test_active_conversation_can_still_reply(): void
    {
        [$conversation, $owner] = $this->conversationBetweenOwnerAndBuyer();

        $this->actingAs($owner)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page->where('canReply', true));
    }

    public function test_new_conversations_snapshot_the_listing_title(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create(['title' => 'Harbor View']);

        $this->actingAs(User::factory()->regularUser()->create())
            ->post("/listings/{$listing->id}/conversations");

        $this->assertDatabaseHas('conversations', ['listing_id' => $listing->id, 'listing_title' => 'Harbor View']);
    }

    public function test_soft_deleted_listing_does_not_break_the_inbox(): void
    {
        [$conversation, $owner, $buyer] = $this->conversationBetweenOwnerAndBuyer();
        $conversation->listing->delete();

        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversations.0.listing.title', 'Sunny Loft')
                ->where('conversations.0.listing.slug', null));
    }
}
