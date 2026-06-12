<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_view_the_forgot_password_page(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_valid_email_request_returns_generic_success_and_dispatches_notification(): void
    {
        Notification::fake();

        $user = User::factory()->regularUser()->create();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('success', __('auth.reset_link_sent'));

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_returns_the_same_generic_success(): void
    {
        Notification::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'nobody@example.test'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('success', __('auth.reset_link_sent'));

        Notification::assertNothingSent();
    }

    public function test_invalid_email_format_fails_validation(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'not-an-email'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');
    }

    public function test_guest_can_view_the_reset_password_page(): void
    {
        $user = User::factory()->regularUser()->create();
        $token = Password::createToken($user);

        $this->get("/reset-password/{$token}?email={$user->email}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', $token)
                ->where('email', $user->email)
            );
    }

    public function test_valid_reset_updates_password_and_redirects_to_login(): void
    {
        Event::fake([PasswordReset::class]);

        $user = User::factory()->regularUser()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertRedirect('/login')
            ->assertSessionHas('success', __('auth.reset_success'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        Event::assertDispatched(PasswordReset::class);
    }

    public function test_user_can_log_in_with_the_new_password_after_reset(): void
    {
        $user = User::factory()->regularUser()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])->assertRedirect('/profile');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_token_returns_generic_error_flash(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->from('/reset-password/invalid-token')
            ->post('/reset-password', [
                'token' => 'invalid-token',
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHas('error', __('auth.reset_failed'));

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_mismatched_passwords_fail_validation(): void
    {
        $user = User::factory()->regularUser()->create();
        $token = Password::createToken($user);

        $this->from("/reset-password/{$token}")
            ->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_weak_password_fails_validation(): void
    {
        $user = User::factory()->regularUser()->create();
        $token = Password::createToken($user);

        $this->from("/reset-password/{$token}")
            ->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_authenticated_user_is_redirected_away_from_forgot_password(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->get('/forgot-password')
            ->assertRedirect('/');
    }

    public function test_authenticated_user_is_redirected_away_from_reset_password(): void
    {
        $user = User::factory()->regularUser()->create();
        $token = Password::createToken($user);

        $this->actingAs($user)
            ->get("/reset-password/{$token}")
            ->assertRedirect('/');
    }
}
