<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
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

    public function test_guest_can_view_the_register_page(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
    }

    public function test_regular_user_registration_succeeds(): void
    {
        $this->post('/register', $this->validPayload())
            ->assertRedirect('/profile');

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::RegularUser, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_agency_registration_succeeds_and_redirects_to_setup(): void
    {
        $this->post('/register', $this->validPayload([
            'account_type' => UserRole::Agency->value,
            'email' => 'agency@example.com',
        ]))->assertRedirect('/agency/setup');

        $user = User::where('email', 'agency@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Agency, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_is_hashed_not_stored_in_plain_text(): void
    {
        $this->post('/register', $this->validPayload());

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotSame('password123', $user->password);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->from('/register')
            ->post('/register', $this->validPayload())
            ->assertRedirect('/register')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->from('/register')
            ->post('/register', $this->validPayload([
                'password' => 'short',
                'password_confirmation' => 'short',
            ]))
            ->assertRedirect('/register')
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_password_mismatch_is_rejected(): void
    {
        $this->from('/register')
            ->post('/register', $this->validPayload([
                'password_confirmation' => 'different123',
            ]))
            ->assertRedirect('/register')
            ->assertSessionHasErrors('password');
    }

    public function test_missing_terms_acceptance_is_rejected(): void
    {
        $this->from('/register')
            ->post('/register', $this->validPayload(['terms_accepted' => false]))
            ->assertRedirect('/register')
            ->assertSessionHasErrors('terms_accepted');

        $this->assertGuest();
    }

    public function test_missing_privacy_acceptance_is_rejected(): void
    {
        $this->from('/register')
            ->post('/register', $this->validPayload(['privacy_accepted' => false]))
            ->assertRedirect('/register')
            ->assertSessionHasErrors('privacy_accepted');

        $this->assertGuest();
    }

    public function test_admin_account_type_is_forbidden(): void
    {
        $this->post('/register', $this->validPayload([
            'account_type' => UserRole::Admin->value,
        ]))->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_register(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->get('/register')
            ->assertRedirect('/');
    }
}
