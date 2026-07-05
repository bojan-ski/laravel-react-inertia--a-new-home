<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Events\ChatMessageSent;
use App\Models\Agency;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConversationChatTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function ownedListing(User $owner): Listing
    {
        return Listing::factory()->approved()->for($owner, 'owner')->create();
    }

    private function suspend(User $user): void
    {
        $user->status = UserStatus::Suspended;
        $user->suspended_at = now();
        $user->save();
    }

    // ---- Starting a conversation ----------------------------------------

    public function test_user_can_start_conversation_on_regular_user_owned_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $buyer = User::factory()->regularUser()->create();

        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations")
            ->assertRedirect();

        $conversation = Conversation::firstOrFail();

        $this->assertSame($listing->id, $conversation->listing_id);
        $this->assertSame($buyer->id, $conversation->buyer_id);
        $this->assertSame($owner->id, $conversation->owner_id);
    }

    public function test_start_redirects_to_the_conversation_show_page(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $buyer = User::factory()->regularUser()->create();

        $this->actingAs($buyer)->post("/listings/{$listing->id}/conversations");

        $conversation = Conversation::firstOrFail();

        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations")
            ->assertRedirect("/conversations/{$conversation->id}");
    }

    public function test_optional_opening_message_is_created_as_first_message(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $buyer = User::factory()->regularUser()->create();

        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Is this still available?']);

        $conversation = Conversation::firstOrFail();

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'body' => 'Is this still available?',
        ]);
    }

    public function test_start_without_message_creates_no_message(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $buyer = User::factory()->regularUser()->create();

        $this->actingAs($buyer)->post("/listings/{$listing->id}/conversations");

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_starting_twice_reuses_the_existing_conversation(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $buyer = User::factory()->regularUser()->create();

        $this->actingAs($buyer)->post("/listings/{$listing->id}/conversations");
        $this->actingAs($buyer)->post("/listings/{$listing->id}/conversations");

        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_user_cannot_start_conversation_on_their_own_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);

        $this->actingAs($owner)
            ->post("/listings/{$listing->id}/conversations")
            ->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_user_cannot_start_conversation_on_agency_owned_listing(): void
    {
        $agency = Agency::factory()->approved()->create();
        $listing = Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);
        $buyer = User::factory()->regularUser()->create();

        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations")
            ->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_user_cannot_start_conversation_on_non_public_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($owner, 'owner')->create();
        $buyer = User::factory()->regularUser()->create();

        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations")
            ->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_user_cannot_start_conversation_on_suspended_owner_listing(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $this->suspend($owner);
        $buyer = User::factory()->regularUser()->create();

        $this->actingAs($buyer)
            ->post("/listings/{$listing->id}/conversations")
            ->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_guest_cannot_start_conversation(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);

        $this->post("/listings/{$listing->id}/conversations")->assertRedirect('/login');

        $this->assertDatabaseCount('conversations', 0);
    }

    // ---- Viewing a conversation -----------------------------------------

    public function test_participant_can_view_conversation(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Conversations/Show'));
    }

    public function test_non_participant_cannot_view_conversation_and_gets_404(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $stranger = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($stranger)
            ->get("/conversations/{$conversation->id}")
            ->assertNotFound();
    }

    public function test_suspended_user_cannot_view_conversation_and_gets_403(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->suspend($buyer);

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertForbidden();
    }

    public function test_other_participant_suspended_hides_conversation_from_view(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->suspend($owner);

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_view_conversation(): void
    {
        $conversation = Conversation::factory()->create();

        $this->get("/conversations/{$conversation->id}")->assertRedirect('/login');
    }

    public function test_show_payload_reflects_the_other_participant_relative_to_the_actor(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page->where('conversation.otherParticipant.id', $owner->id));

        $this->actingAs($owner)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page->where('conversation.otherParticipant.id', $buyer->id));
    }

    public function test_show_payload_orders_messages_chronologically(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $newer = ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'body' => 'Second',
            'created_at' => now(),
        ]);
        $older = ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $owner->id,
            'body' => 'First',
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversation.messages.0.id', $older->id)
                ->where('conversation.messages.1.id', $newer->id));
    }

    // ---- Sending messages -----------------------------------------------

    public function test_participant_can_send_a_message(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Hello owner'])
            ->assertRedirect("/conversations/{$conversation->id}");

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'body' => 'Hello owner',
        ]);
    }

    public function test_sending_a_message_touches_the_conversation_updated_at(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);
        $original = $conversation->updated_at;

        $this->travel(5)->minutes();

        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Hello']);

        $this->assertTrue($conversation->fresh()->updated_at->gt($original));
    }

    public function test_non_participant_cannot_send_a_message(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $stranger = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($stranger)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Intruding'])
            ->assertForbidden();

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_suspended_user_cannot_send_a_message(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->suspend($buyer);

        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Hello'])
            ->assertForbidden();

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_message_body_is_required(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_broadcast_event_is_dispatched_when_a_message_is_sent(): void
    {
        Event::fake([ChatMessageSent::class]);

        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Broadcast me']);

        Event::assertDispatched(ChatMessageSent::class, function (ChatMessageSent $event) use ($buyer) {
            return $event->message->sender_id === $buyer->id
                && $event->message->body === 'Broadcast me';
        });
    }

    // ---- Inbox ----------------------------------------------------------

    public function test_inbox_shows_only_the_users_conversations(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        Conversation::factory()->create();

        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Conversations/Index')
                ->has('conversations', 1)
                ->where('conversations.0.id', $conversation->id));
    }

    public function test_inbox_is_ordered_by_most_recent_activity(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $ownerA = User::factory()->regularUser()->create();
        $ownerB = User::factory()->regularUser()->create();
        $listingA = $this->ownedListing($ownerA);
        $listingB = $this->ownedListing($ownerB);

        $convA = Conversation::factory()->create([
            'listing_id' => $listingA->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $ownerA->id,
        ]);
        $convB = Conversation::factory()->create([
            'listing_id' => $listingB->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $ownerB->id,
        ]);

        $this->travel(10)->minutes();

        // Bump conversation A so it becomes the most recent.
        $this->actingAs($buyer)->post("/conversations/{$convA->id}/messages", ['body' => 'Bump']);

        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversations.0.id', $convA->id)
                ->where('conversations.1.id', $convB->id));
    }

    public function test_inbox_excludes_conversations_with_a_suspended_other_participant(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($owner);
        Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->suspend($owner);

        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page->has('conversations', 0));
    }

    public function test_guest_cannot_view_inbox(): void
    {
        $this->get('/conversations')->assertRedirect('/login');
    }
}
