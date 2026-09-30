<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConversationPayloadTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function conversationOn(Listing $listing, User $buyer): Conversation
    {
        return Conversation::factory()->create([
            'listing_id' => $listing->id,
            'listing_title' => $listing->title,
            'buyer_id' => $buyer->id,
            'owner_id' => $listing->user_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function inboxListing(User $viewer, Conversation $conversation): array
    {
        $rows = collect($this->actingAs($viewer)->get('/conversations')->inertiaProps('conversations'));

        return $rows->firstWhere('id', $conversation->id)['listing'];
    }

    public function test_inbox_row_carries_the_listing_card_fields(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create([
            'listing_type' => 'rent',
            'price' => 1250,
            'city' => 'Belgrade',
        ]);
        $conversation = $this->conversationOn($listing, $buyer);

        $summary = $this->inboxListing($buyer, $conversation);

        $this->assertSame('Belgrade', $summary['city']);
        $this->assertSame('1250.00', $summary['price']);
        $this->assertSame('EUR', $summary['currency']);
        $this->assertSame('rent', $summary['listingType']);
    }

    public function test_inbox_row_uses_the_first_image_by_sort_order_in_its_small_variant(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create();
        ListingImage::factory()->for($listing)->create(['sort_order' => 1, 'small_path' => 'listings/small/second.webp']);
        ListingImage::factory()->for($listing)->create(['sort_order' => 0, 'small_path' => 'listings/small/first.webp']);
        $conversation = $this->conversationOn($listing, $buyer);

        $summary = $this->inboxListing($buyer, $conversation);

        $this->assertSame(Storage::disk('public')->url('listings/small/first.webp'), $summary['imageUrl']);
    }

    public function test_inbox_row_image_is_null_without_images(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationOn(Listing::factory()->approved()->create(), $buyer);

        $this->assertNull($this->inboxListing($buyer, $conversation)['imageUrl']);
    }

    public function test_live_listing_is_available_and_not_expired(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationOn(Listing::factory()->approved()->create(), $buyer);

        $summary = $this->inboxListing($buyer, $conversation);

        $this->assertTrue($summary['isAvailable']);
        $this->assertFalse($summary['isExpired']);
    }

    public function test_expired_listing_is_expired_and_unavailable(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationOn(Listing::factory()->expired()->create(), $buyer);

        $summary = $this->inboxListing($buyer, $conversation);

        $this->assertTrue($summary['isExpired']);
        $this->assertFalse($summary['isAvailable']);
    }

    public function test_sold_pending_and_hidden_listings_are_unavailable_and_not_expired(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $conversations = [
            $this->conversationOn(Listing::factory()->sold()->create(), $buyer),
            $this->conversationOn(Listing::factory()->pendingReview()->create(), $buyer),
            $this->conversationOn(Listing::factory()->hidden()->create(), $buyer),
        ];

        foreach ($conversations as $conversation) {
            $summary = $this->inboxListing($buyer, $conversation);

            $this->assertFalse($summary['isAvailable']);
            $this->assertFalse($summary['isExpired']);
        }
    }

    public function test_removed_listing_falls_back_to_the_snapshot_title_and_null_fields(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->create(['title' => 'Old Riverside Flat']);
        ListingImage::factory()->for($listing)->create();
        $conversation = $this->conversationOn($listing, $buyer);

        $listing->delete();

        $summary = $this->inboxListing($buyer, $conversation);

        $this->assertNull($summary['id']);
        $this->assertSame('Old Riverside Flat', $summary['title']);
        $this->assertNull($summary['city']);
        $this->assertNull($summary['price']);
        $this->assertNull($summary['imageUrl']);
        $this->assertFalse($summary['isAvailable']);
        $this->assertFalse($summary['isExpired']);
    }

    public function test_last_message_carries_the_sender_id(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationOn(Listing::factory()->approved()->create(), $buyer);
        ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
        ]);

        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversations.0.lastMessage.senderId', $buyer->id));
    }

    public function test_last_message_sender_id_is_null_for_a_deleted_sender(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationOn(Listing::factory()->approved()->create(), $buyer);
        ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => null,
        ]);

        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page
                ->has('conversations.0.lastMessage.senderId')
                ->where('conversations.0.lastMessage.senderId', null));
    }

    public function test_show_listing_summary_carries_the_same_fields(): void
    {
        $buyer = User::factory()->regularUser()->create();
        $listing = Listing::factory()->expired()->create([
            'listing_type' => 'sale',
            'price' => 240000,
            'city' => 'Novi Sad',
        ]);
        ListingImage::factory()->for($listing)->create(['small_path' => 'listings/small/cover.webp']);
        $conversation = $this->conversationOn($listing, $buyer);

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversation.listing.imageUrl', Storage::disk('public')->url('listings/small/cover.webp'))
                ->where('conversation.listing.city', 'Novi Sad')
                ->where('conversation.listing.price', '240000.00')
                ->where('conversation.listing.currency', 'EUR')
                ->where('conversation.listing.listingType', 'sale')
                ->where('conversation.listing.isExpired', true)
                ->where('conversation.listing.isAvailable', false));
    }

    public function test_translations_carry_the_messages_copy(): void
    {
        $buyer = User::factory()->regularUser()->create();

        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page
                ->where('translations.select_prompt', __('app.conversations.select_prompt'))
                ->where('translations.you_prefix', __('app.conversations.you_prefix'))
                ->where('translations.per_month', __('app.conversations.per_month'))
                ->where('translations.today', __('app.conversations.today'))
                ->where('translations.yesterday', __('app.conversations.yesterday'))
                ->where('translations.listing_status.expired', __('app.conversations.listing_status.expired')));
    }

    public function test_inbox_query_count_does_not_grow_with_conversations(): void
    {
        $buyer = User::factory()->regularUser()->create();

        $countQueries = function () use ($buyer): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($buyer)->get('/conversations')->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $createConversations = function (int $count) use ($buyer): void {
            foreach (range(1, $count) as $index) {
                $listing = Listing::factory()->approved()->create();
                ListingImage::factory()->for($listing)->create();
                $conversation = $this->conversationOn($listing, $buyer);
                ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $conversation->owner_id]);
            }
        };

        $createConversations(2);
        $withTwo = $countQueries();

        $createConversations(3);
        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page->has('conversations', 5));

        $this->assertSame($withTwo, $countQueries());
    }
}
