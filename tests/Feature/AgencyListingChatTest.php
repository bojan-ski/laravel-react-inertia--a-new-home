<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Events\ChatMessageReceived;
use App\Events\ChatMessageSent;
use App\Models\Agency;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencyListingChatTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function agency(): Agency
    {
        return Agency::factory()->approved()->create([
            'name' => 'Adriatic Homes',
            'user_id' => User::factory()->agency()->state(['name' => 'Marija Horvat']),
        ]);
    }

    private function agencyListing(Agency $agency): Listing
    {
        return Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);
    }

    /**
     * @return array{Conversation, User, User}
     */
    private function agencyConversation(): array
    {
        $agency = $this->agency();
        $listing = $this->agencyListing($agency);
        $buyer = User::factory()->regularUser()->create(['name' => 'Mia Buyer']);

        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'listing_title' => $listing->title,
            'buyer_id' => $buyer->id,
            'owner_id' => $agency->user_id,
        ]);

        return [$conversation, $agency->user, $buyer];
    }

    private function message(Conversation $conversation, User $sender, string $body): ChatMessage
    {
        return ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);
    }

    public function test_listing_page_offers_chat_on_an_agency_listing(): void
    {
        $listing = $this->agencyListing($this->agency());

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('canStartChat', true));
    }

    public function test_agency_account_cannot_start_a_chat_on_its_own_listing(): void
    {
        $agency = $this->agency();
        $listing = $this->agencyListing($agency);

        $this->actingAs($agency->user)
            ->get("/listings/{$listing->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('canStartChat', false));

        $this->actingAs($agency->user)
            ->post("/listings/{$listing->id}/conversations")
            ->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_agency_account_can_chat_about_another_agencys_listing_under_its_own_name(): void
    {
        $agency = $this->agency();
        $listing = $this->agencyListing($agency);
        $otherAgency = Agency::factory()->approved()->create([
            'name' => 'Coastal Realty',
            'user_id' => User::factory()->agency()->state(['name' => 'Ivo Kovac']),
        ]);

        $this->actingAs($otherAgency->user)
            ->post("/listings/{$listing->id}/conversations")
            ->assertRedirect();

        $conversation = Conversation::firstOrFail();

        $this->actingAs($agency->user)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page->where('conversation.otherParticipant.name', 'Ivo Kovac'));

        $this->actingAs($otherAgency->user)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page->where('conversation.otherParticipant.name', 'Adriatic Homes'));
    }

    public function test_only_the_owner_side_is_named_after_its_agency(): void
    {
        [$conversation, $agencyAccount] = $this->agencyConversation();
        $buyerAgency = Agency::factory()->approved()->create([
            'name' => 'Coastal Realty',
            'user_id' => User::factory()->agency()->state(['name' => 'Ivo Kovac']),
        ]);
        $agencyBuyer = $buyerAgency->user->load('agency');
        $conversation->buyer_id = $agencyBuyer->id;

        $this->assertSame('Ivo Kovac', $conversation->participantName($agencyBuyer));
        $this->assertSame('Adriatic Homes', $conversation->participantName($agencyAccount->load('agency')));
    }

    public function test_no_chat_when_the_agency_account_is_suspended(): void
    {
        $agency = $this->agency();
        $listing = $this->agencyListing($agency);
        $agency->user->forceFill(['status' => UserStatus::Suspended, 'suspended_at' => now()])->save();

        $this->actingAs(User::factory()->regularUser()->create())
            ->post("/listings/{$listing->id}/conversations")
            ->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_buyer_sees_the_agency_name_in_the_thread_and_inbox(): void
    {
        [$conversation, $agencyAccount, $buyer] = $this->agencyConversation();
        $this->message($conversation, $buyer, 'Is it still available?');
        $this->message($conversation, $agencyAccount, 'Yes, it is.');

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversation.otherParticipant.name', 'Adriatic Homes')
                ->where('conversation.messages.0.senderName', 'Mia Buyer')
                ->where('conversation.messages.1.senderName', 'Adriatic Homes')
            );

        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversations.0.otherParticipant.name', 'Adriatic Homes')
            );
    }

    public function test_agency_account_still_sees_the_buyers_own_name(): void
    {
        [$conversation, $agencyAccount, $buyer] = $this->agencyConversation();
        $this->message($conversation, $buyer, 'Is it still available?');

        $this->actingAs($agencyAccount)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversation.otherParticipant.name', 'Mia Buyer')
                ->where('conversation.messages.0.senderName', 'Mia Buyer')
            );

        $this->actingAs($agencyAccount)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversations.0.otherParticipant.name', 'Mia Buyer')
            );
    }

    public function test_broadcasts_name_the_agency_when_the_agency_replies(): void
    {
        [$conversation, $agencyAccount, $buyer] = $this->agencyConversation();
        $reply = $this->message($conversation, $agencyAccount, 'Yes, it is.');

        $this->assertSame('Adriatic Homes', (new ChatMessageSent($reply))->broadcastWith()['senderName']);
        $this->assertSame('Adriatic Homes', (new ChatMessageReceived($reply, $buyer->id))->broadcastWith()['senderName']);

        $question = $this->message($conversation, $buyer, 'Can I visit?');

        $this->assertSame('Mia Buyer', (new ChatMessageReceived($question, $agencyAccount->id))->broadcastWith()['senderName']);
    }

    public function test_a_regular_owner_is_still_shown_by_their_own_name(): void
    {
        $owner = User::factory()->regularUser()->create(['name' => 'Sam Owner']);
        $buyer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);
        $this->message($conversation, $owner, 'Hello');

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversation.otherParticipant.name', 'Sam Owner')
                ->where('conversation.messages.0.senderName', 'Sam Owner')
            );
    }
}
