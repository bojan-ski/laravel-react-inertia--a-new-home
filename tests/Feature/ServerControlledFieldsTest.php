<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\ListingImage;
use App\Models\ReportedChatMessage;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServerControlledFieldsTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string<Model>, 1: list<string>}>
     */
    public static function serverControlledFields(): array
    {
        return [
            'reported chat message' => [ReportedChatMessage::class, ['chat_message_id', 'reported_by_user_id', 'offending_user_id', 'resolved_at', 'resolved_by_user_id']],
            'conversation' => [Conversation::class, ['listing_id', 'buyer_id', 'owner_id']],
            'chat message' => [ChatMessage::class, ['conversation_id', 'sender_id']],
            'bookmark' => [Bookmark::class, ['user_id', 'listing_id']],
            'listing image' => [ListingImage::class, ['listing_id']],
        ];
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<string>  $fields
     */
    #[DataProvider('serverControlledFields')]
    public function test_server_controlled_fields_cannot_be_mass_assigned(string $model, array $fields): void
    {
        try {
            $instance = (new $model)->fill(array_fill_keys($fields, 1));
        } catch (MassAssignmentException) {
            $this->assertTrue((new $model)->totallyGuarded());

            return;
        }

        $this->assertSame([], array_intersect_key($instance->getAttributes(), array_flip($fields)));
    }
}
