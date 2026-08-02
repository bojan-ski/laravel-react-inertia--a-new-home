<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
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

    // ---- Rate limiting --------------------------------------------------

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $user = User::factory()->regularUser()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(429);
    }

    public function test_register_is_rate_limited_after_five_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/register', []);
        }

        $this->post('/register', [])->assertStatus(429);
    }

    public function test_forgot_password_is_rate_limited_after_five_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/forgot-password', ['email' => 'someone@example.test']);
        }

        $this->post('/forgot-password', ['email' => 'someone@example.test'])
            ->assertStatus(429);
    }

    public function test_reset_password_is_rate_limited_after_five_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/reset-password', ['token' => 'x', 'email' => 'someone@example.test']);
        }

        $this->post('/reset-password', ['token' => 'x', 'email' => 'someone@example.test'])
            ->assertStatus(429);
    }

    // ---- Session fixation -----------------------------------------------

    public function test_registration_regenerates_the_session(): void
    {
        $this->get('/register');
        $originalSessionId = session()->getId();

        $this->post('/register', $this->registerPayload());

        $this->assertNotSame($originalSessionId, session()->getId());
        $this->assertAuthenticated();
    }

    public function test_login_regenerates_the_session(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->get('/login');
        $originalSessionId = session()->getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertNotSame($originalSessionId, session()->getId());
        $this->assertAuthenticatedAs($user);
    }

    // ---- Suspension on plain-auth read routes ---------------------------

    public function test_suspended_user_is_booted_from_listing_detail(): void
    {
        $listing = Listing::factory()->approved()->create();
        $suspended = User::factory()->regularUser()->suspended()->create();

        $this->actingAs($suspended)
            ->get("/listings/{$listing->slug}")
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_suspended_user_is_booted_from_bookmarks(): void
    {
        $suspended = User::factory()->regularUser()->suspended()->create();

        $this->actingAs($suspended)
            ->get('/bookmarks')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_suspended_user_is_booted_from_conversations_inbox(): void
    {
        $suspended = User::factory()->regularUser()->suspended()->create();

        $this->actingAs($suspended)
            ->get('/conversations')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_active_user_is_not_affected_by_the_suspension_guard(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $viewer = User::factory()->regularUser()->create();

        $this->actingAs($viewer)->get("/listings/{$listing->slug}")->assertOk();
        $this->actingAs($viewer)->get('/bookmarks')->assertOk();
        $this->actingAs($viewer)->get('/conversations')->assertOk();
    }

    public function test_conversation_thread_still_denies_a_suspended_participant_with_403(): void
    {
        $owner = User::factory()->regularUser()->create();
        $buyer = User::factory()->regularUser()->suspended()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        $conversation = Conversation::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertForbidden();
    }
}
