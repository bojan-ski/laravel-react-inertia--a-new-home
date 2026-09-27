<?php

namespace Tests\Feature;

use App\Events\ChatMessageReceived;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\UserAccountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MessageNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array{Conversation, User, User}
     */
    private function conversation(): array
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create(['title' => 'Garden Flat']);

        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'listing_title' => 'Garden Flat',
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        return [$conversation, $owner, $buyer];
    }

    private function message(Conversation $conversation, User $sender, string $body = 'Hello'): ChatMessage
    {
        return ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);
    }

    public function test_guests_get_no_unread_count_or_notification_copy(): void
    {
        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('unreadMessages', 0)
                ->where('messageNotifications', null));
    }

    public function test_signed_in_users_get_the_notification_copy(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('messageNotifications', __('app.message_notifications'))
                ->where('nav.messages', __('app.nav.messages'))
                ->where('listingsNav.messages', __('app.listings.nav.messages')));
    }

    public function test_messages_from_the_other_side_count_as_unread(): void
    {
        [$conversation, $owner, $buyer] = $this->conversation();
        $this->message($conversation, $buyer, 'First');
        $this->message($conversation, $buyer, 'Second');
        $this->message($conversation, $owner, 'Own reply');

        $this->actingAs($owner)
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('unreadMessages', 2));

        $this->actingAs($buyer)
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('unreadMessages', 1));
    }

    public function test_opening_the_conversation_marks_it_read(): void
    {
        [$conversation, $owner, $buyer] = $this->conversation();
        $this->message($conversation, $buyer);

        $this->actingAs($owner)->get("/conversations/{$conversation->id}")->assertOk();

        $this->actingAs($owner)
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('unreadMessages', 0));

        $this->assertNotNull($conversation->fresh()->owner_last_read_at);
        $this->assertNull($conversation->fresh()->buyer_last_read_at);
    }

    public function test_reading_does_not_reorder_the_inbox(): void
    {
        [$conversation, $owner, $buyer] = $this->conversation();
        $this->message($conversation, $buyer);
        $updatedAt = $conversation->fresh()->updated_at;

        $this->travel(5)->minutes();
        $this->actingAs($owner)->get("/conversations/{$conversation->id}");

        $this->assertEquals($updatedAt, $conversation->fresh()->updated_at);
    }

    public function test_messages_after_the_read_marker_are_unread_again(): void
    {
        [$conversation, $owner, $buyer] = $this->conversation();
        $this->message($conversation, $buyer);
        $this->actingAs($owner)->get("/conversations/{$conversation->id}");

        $this->travel(1)->minute();
        $this->message($conversation, $buyer, 'Follow-up');

        $this->actingAs($owner)
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('unreadMessages', 1));
    }

    public function test_other_users_conversations_are_not_counted(): void
    {
        [$conversation, , $buyer] = $this->conversation();
        $this->message($conversation, $buyer);

        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('unreadMessages', 0));
    }

    public function test_messages_from_suspended_senders_are_not_counted(): void
    {
        [$conversation, $owner, $buyer] = $this->conversation();
        $this->message($conversation, $buyer);
        $buyer->forceFill(['status' => 'suspended', 'suspended_at' => now()])->save();

        $this->actingAs($owner)
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('unreadMessages', 0));
    }

    public function test_messages_from_deleted_senders_still_count_until_read(): void
    {
        [$conversation, $owner, $buyer] = $this->conversation();
        $this->message($conversation, $buyer);
        app(UserAccountService::class)->delete($buyer);

        $this->actingAs($owner)
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('unreadMessages', 1));

        $this->actingAs($owner)->get("/conversations/{$conversation->id}");

        $this->actingAs($owner)
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('unreadMessages', 0));
    }

    public function test_inbox_rows_carry_their_unread_count(): void
    {
        [$unreadConversation, $owner, $buyer] = $this->conversation();
        $this->message($unreadConversation, $buyer, 'One');
        $this->message($unreadConversation, $buyer, 'Two');

        $this->travel(1)->minute();

        $readListing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $readConversation = Conversation::factory()->create([
            'listing_id' => $readListing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);
        $this->message($readConversation, $buyer);
        $this->actingAs($owner)->get("/conversations/{$readConversation->id}");

        $this->actingAs($owner)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversations.0.id', $readConversation->id)
                ->where('conversations.0.unreadCount', 0)
                ->where('conversations.1.id', $unreadConversation->id)
                ->where('conversations.1.unreadCount', 2)
                ->where('translations.unread', __('app.conversations.unread')));
    }

    public function test_sending_a_message_notifies_the_recipient_channel(): void
    {
        Event::fake([ChatMessageReceived::class]);
        [$conversation, $owner, $buyer] = $this->conversation();

        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Can I visit on Friday?'])
            ->assertRedirect();

        Event::assertDispatched(
            ChatMessageReceived::class,
            fn (ChatMessageReceived $event): bool => $event->recipientId === $owner->id
                && $event->broadcastOn()->name === "private-App.Models.User.{$owner->id}",
        );
    }

    public function test_received_broadcast_payload(): void
    {
        [$conversation, $owner, $buyer] = $this->conversation();
        $buyer->forceFill(['name' => 'Mia Buyer'])->save();
        $message = $this->message($conversation, $buyer, 'Can I visit on Friday?');

        $event = new ChatMessageReceived($message, $owner->id);

        $this->assertSame('ChatMessageReceived', $event->broadcastAs());
        $this->assertSame([
            'conversationId' => $conversation->id,
            'senderName' => 'Mia Buyer',
            'preview' => 'Can I visit on Friday?',
            'listingTitle' => 'Garden Flat',
        ], $event->broadcastWith());
    }

    public function test_no_notification_when_the_recipient_account_is_gone(): void
    {
        Event::fake([ChatMessageReceived::class]);
        [$conversation, $owner, $buyer] = $this->conversation();
        $conversation->forceFill(['owner_id' => null])->save();

        app(ConversationService::class)->sendMessage($conversation, $buyer, 'Hello?');

        Event::assertNotDispatched(ChatMessageReceived::class);
    }
}
