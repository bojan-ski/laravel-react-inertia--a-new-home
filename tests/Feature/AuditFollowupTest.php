<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\ListingImageService;
use App\Services\ListingService;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class AuditFollowupTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function attachImage(Listing $listing): ListingImage
    {
        return app(ListingImageService::class)->upload(
            $listing,
            UploadedFile::fake()->image('photo.jpg', 800, 600)
        );
    }

    /**
     * Invoke the real registered broadcast channel authorization callback,
     * independent of the configured broadcast driver.
     */
    private function canAccessChannel(User $user, string $channel): bool
    {
        $broadcaster = app(BroadcastFactory::class)->connection();

        $request = Request::create('/broadcasting/auth', 'POST');
        $request->setUserResolver(fn () => $user);

        $verify = new ReflectionMethod($broadcaster, 'verifyUserCanAccessChannel');
        $verify->setAccessible(true);

        try {
            $verify->invoke($broadcaster, $request, $channel);

            return true;
        } catch (AccessDeniedHttpException) {
            return false;
        }
    }

    // ---- #1 — image files are cleaned up on user hard-delete ------------

    public function test_deleting_a_regular_user_removes_their_listing_image_files(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $image = $this->attachImage($listing);

        Storage::disk('public')->assertExists($image->small_path);

        $this->actingAs($admin)->delete("/admin/users/{$owner->id}")->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->large_path);
    }

    public function test_deleting_an_agency_user_removes_their_agency_listing_image_files(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $agencyUser = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $agencyUser->id]);
        $listing = Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);
        $image = $this->attachImage($listing);

        Storage::disk('public')->assertExists($image->large_path);

        $this->actingAs($admin)->delete("/admin/users/{$agencyUser->id}")->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $agencyUser->id]);
        $this->assertDatabaseMissing('agencies', ['id' => $agency->id]);
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->large_path);
    }

    public function test_deleting_a_user_without_listings_is_still_safe(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->regularUser()->create();

        $this->actingAs($admin)->delete("/admin/users/{$owner->id}")->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
    }

    public function test_deleting_a_user_removes_image_files_for_rejected_soft_deleted_listings(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->pendingReview()->for($owner, 'owner')->create();
        $image = $this->attachImage($listing);

        app(ListingService::class)->reject($listing, 'Rejected.');

        Storage::disk('public')->assertExists($image->small_path);

        $this->actingAs($admin)->delete("/admin/users/{$owner->id}")->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->large_path);
    }

    // ---- #2 — agency attribution in payloads ----------------------------

    public function test_moderation_queue_shows_agency_attribution_for_agency_listings(): void
    {
        $agency = Agency::factory()->approved()->create([
            'name' => 'Premier Properties',
            'email' => 'contact@premier.test',
        ]);
        $listing = Listing::factory()->pendingReview()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $listing->id)
                ->where('listings.data.0.ownerName', 'Premier Properties')
                ->where('listings.data.0.ownerEmail', 'contact@premier.test'));
    }

    public function test_public_card_shows_agency_name_for_agency_listings(): void
    {
        $agency = Agency::factory()->approved()->create(['name' => 'Crown Estates']);
        $listing = Listing::factory()->approved()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
        ]);

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.data.0.id', $listing->id)
                ->where('listings.data.0.ownerName', 'Crown Estates'));
    }

    // ---- #3 — broadcast channel authorization ---------------------------

    private function conversationBetween(User $buyer, User $owner): Conversation
    {
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        return Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);
    }

    public function test_participant_can_access_the_conversation_channel(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationBetween($buyer, $owner);

        $this->assertTrue($this->canAccessChannel($buyer, "conversation.{$conversation->id}"));
        $this->assertTrue($this->canAccessChannel($owner, "conversation.{$conversation->id}"));
    }

    public function test_non_participant_cannot_access_the_conversation_channel(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->create();
        $stranger = User::factory()->regularUser()->create();
        $conversation = $this->conversationBetween($buyer, $owner);

        $this->assertFalse($this->canAccessChannel($stranger, "conversation.{$conversation->id}"));
    }

    public function test_suspended_participant_cannot_access_the_conversation_channel(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->suspended()->create();
        $conversation = $this->conversationBetween($buyer, $owner);

        $this->assertFalse($this->canAccessChannel($buyer, "conversation.{$conversation->id}"));
    }

    public function test_channel_denies_when_the_other_participant_is_suspended(): void
    {
        $owner = User::factory()->regularUser()->suspended()->create();
        $buyer = User::factory()->regularUser()->create();
        $conversation = $this->conversationBetween($buyer, $owner);

        $this->assertFalse($this->canAccessChannel($buyer, "conversation.{$conversation->id}"));
    }

    public function test_channel_denies_a_missing_conversation(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->assertFalse($this->canAccessChannel($user, 'conversation.999999'));
    }

    public function test_user_can_only_access_their_own_notification_channel(): void
    {
        $user = User::factory()->regularUser()->create();
        $other = User::factory()->regularUser()->create();

        $this->assertTrue($this->canAccessChannel($user, "App.Models.User.{$user->id}"));
        $this->assertFalse($this->canAccessChannel($user, "App.Models.User.{$other->id}"));
    }
}
