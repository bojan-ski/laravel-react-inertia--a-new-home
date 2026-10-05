<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\ChatMessage;
use App\Models\ContactMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FormRateLimitTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'account_type' => UserRole::RegularUser->value,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function contactPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'visitor@example.com',
            'subject' => 'Question',
            'message' => 'Hello there.',
        ], $overrides);
    }

    private function repeat(int $times, callable $request): void
    {
        for ($attempt = 0; $attempt < $times; $attempt++) {
            $request();
        }
    }

    public function test_login_is_limited_per_ip_across_different_emails(): void
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->post('/login', ['email' => "user{$attempt}@example.com", 'password' => 'wrong-password'])
                ->assertRedirect();
        }

        $this->post('/login', ['email' => 'another@example.com', 'password' => 'wrong-password'])
            ->assertStatus(429);
    }

    public function test_register_is_capped_at_twenty_attempts_per_hour(): void
    {
        for ($minute = 0; $minute < 4; $minute++) {
            $this->repeat(5, fn () => $this->post('/register', [])->assertRedirect());
            $this->travel(61)->seconds();
        }

        $this->post('/register', [])->assertStatus(429);
    }

    public function test_contact_is_capped_at_ten_messages_per_hour(): void
    {
        foreach ([3, 3, 3, 1] as $attempts) {
            $this->repeat($attempts, fn () => $this->post('/contact', [])->assertRedirect());
            $this->travel(61)->seconds();
        }

        $this->post('/contact', [])->assertStatus(429);
    }

    public function test_register_rejects_a_filled_honeypot(): void
    {
        $this->post('/register', $this->registerPayload(['website' => 'https://spam.example']))
            ->assertSessionHasErrors(['website' => __('app.rate_limit.honeypot')]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_register_accepts_an_empty_honeypot(): void
    {
        $this->post('/register', $this->registerPayload(['website' => '']))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    public function test_contact_rejects_a_filled_honeypot(): void
    {
        $this->post('/contact', $this->contactPayload(['website' => 'https://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_contact_accepts_an_empty_honeypot(): void
    {
        $this->post('/contact', $this->contactPayload(['website' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_sending_chat_messages_is_limited_and_shared_with_reports(): void
    {
        $conversation = Conversation::factory()->create();
        $buyer = $conversation->buyer;
        $message = ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $conversation->owner_id,
        ]);

        $this->actingAs($buyer);
        $this->repeat(20, fn () => $this->post("/conversations/{$conversation->id}/messages", [])->assertRedirect());

        $this->post("/conversations/{$conversation->id}/messages", ['body' => 'Hello'])->assertStatus(429);
        $this->post("/messages/{$message->id}/report", [])->assertStatus(429);
    }

    public function test_starting_conversations_is_capped_per_hour(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->create());
        $this->repeat(20, fn () => $this->post("/listings/{$listing->id}/conversations", [])->assertRedirect());

        $this->post("/listings/{$listing->id}/conversations", [])->assertStatus(429);
    }

    public function test_listing_image_uploads_are_limited(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner);
        $this->repeat(20, fn () => $this->post("/my-listings/{$listing->id}/images", [])->assertRedirect());

        $this->post("/my-listings/{$listing->id}/images", [])->assertStatus(429);
    }

    public function test_regular_user_listing_writes_are_limited(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner);
        $this->repeat(5, fn () => $this->post('/my-listings', [])->assertRedirect());
        $this->repeat(5, fn () => $this->put("/my-listings/{$listing->id}", [])->assertRedirect());

        $this->post('/my-listings', [])->assertStatus(429);
    }

    public function test_agency_listing_writes_are_limited(): void
    {
        $agency = Agency::factory()->approved()->create();

        $this->actingAs($agency->user);
        $this->repeat(10, fn () => $this->post('/agency/listings', [])->assertRedirect());

        $this->post('/agency/listings', [])->assertStatus(429);
    }

    public function test_listing_deletes_share_the_listing_write_limit(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->for($owner, 'owner')->create();
        $agency = Agency::factory()->approved()->create();
        $agencyListing = Listing::factory()->create(['user_id' => null, 'agency_id' => $agency->id]);

        $this->actingAs($owner);
        $this->repeat(10, fn () => $this->post('/my-listings', [])->assertRedirect());
        $this->delete("/my-listings/{$listing->id}")->assertStatus(429);

        $this->actingAs($agency->user);
        $this->repeat(10, fn () => $this->post('/agency/listings', [])->assertRedirect());
        $this->delete("/agency/listings/{$agencyListing->id}")->assertStatus(429);

        $this->assertNotSoftDeleted($listing);
        $this->assertNotSoftDeleted($agencyListing);
    }

    public function test_image_deletes_share_the_image_limit(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->for($owner, 'owner')->create();
        $image = ListingImage::factory()->for($listing)->create();
        $agency = Agency::factory()->approved()->create();
        $agencyListing = Listing::factory()->create(['user_id' => null, 'agency_id' => $agency->id]);
        $agencyImage = ListingImage::factory()->for($agencyListing)->create();

        $this->actingAs($owner);
        $this->repeat(20, fn () => $this->post("/my-listings/{$listing->id}/images", [])->assertRedirect());
        $this->delete("/my-listings/{$listing->id}/images/{$image->id}")->assertStatus(429);

        $this->actingAs($agency->user);
        $this->repeat(20, fn () => $this->post("/agency/listings/{$agencyListing->id}/images", [])->assertRedirect());
        $this->delete("/agency/listings/{$agencyListing->id}/images/{$agencyImage->id}")->assertStatus(429);

        $this->assertModelExists($image);
        $this->assertModelExists($agencyImage);
    }

    public function test_saved_search_deletes_share_the_saved_search_limit(): void
    {
        $user = User::factory()->regularUser()->create();
        $search = SavedSearch::factory()->for($user)->create();

        $this->actingAs($user);
        $this->repeat(20, fn () => $this->post('/saved-searches', [])->assertRedirect());

        $this->delete("/saved-searches/{$search->id}")->assertStatus(429);
        $this->assertModelExists($search);
    }

    public function test_agency_profile_writes_are_limited(): void
    {
        $agency = Agency::factory()->approved()->create();

        $this->actingAs($agency->user);
        $this->repeat(10, fn () => $this->put('/agency', [])->assertRedirect());
        $this->put('/agency', [])->assertStatus(429);

        $this->actingAs(User::factory()->agency()->create());
        $this->repeat(10, fn () => $this->post('/agency/setup', [])->assertRedirect());
        $this->post('/agency/setup', [])->assertStatus(429);
    }

    public function test_password_confirmed_actions_are_limited_for_regular_users(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user);
        $this->repeat(5, fn () => $this->put('/profile/settings/password', ['current_password' => 'wrong'])->assertRedirect());

        $this->delete('/profile', ['password' => 'password'])->assertStatus(429);
        $this->assertModelExists($user);
    }

    public function test_password_confirmed_actions_are_limited_for_agencies(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user);
        $this->repeat(5, fn () => $this->put('/agency/settings/password', ['current_password' => 'wrong'])->assertRedirect());

        $this->delete('/agency/account', ['password' => 'password'])->assertStatus(429);
        $this->assertModelExists($user);
    }

    public function test_throttled_inertia_request_redirects_back_with_a_flash(): void
    {
        $headers = ['X-Inertia' => 'true', 'Referer' => url('/login')];

        $this->repeat(5, fn () => $this->post('/login', ['email' => 'jane@example.com', 'password' => 'wrong'], $headers));

        $this->post('/login', ['email' => 'jane@example.com', 'password' => 'wrong'], $headers)
            ->assertRedirect('/login')
            ->assertSessionHas('throttled', 'Too many attempts. Please try again in a minute.');
    }

    public function test_throttled_inertia_request_reports_minutes_for_an_hourly_limit(): void
    {
        $headers = ['X-Inertia' => 'true', 'Referer' => url('/register')];

        for ($minute = 0; $minute < 4; $minute++) {
            $this->repeat(5, fn () => $this->post('/register', [], $headers));
            $this->travel(61)->seconds();
        }

        $this->post('/register', [], $headers)
            ->assertRedirect('/register')
            ->assertSessionHas('throttled', fn (string $message): bool => str_contains($message, 'minutes.'));
    }

    public function test_throttled_flash_is_shared_with_the_page(): void
    {
        $this->withSession(['throttled' => 'Too many attempts. Please try again in a minute.'])
            ->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.throttled', 'Too many attempts. Please try again in a minute.'));
    }
}
