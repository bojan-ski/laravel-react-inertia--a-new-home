<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_view_the_login_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_valid_credentials_log_the_user_in(): void
    {
        $user = User::factory()->regularUser()->create([
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/profile');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_with_error_and_preserve_email(): void
    {
        $user = User::factory()->regularUser()->create([
            'password' => 'password',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $response->assertSessionHasInput('email', $user->email);
        $response->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }

    public function test_suspended_user_is_logged_out_and_returned_with_error_flash(): void
    {
        $user = User::factory()->regularUser()->suspended()->create([
            'password' => 'password',
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect('/login')
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_regular_user_is_redirected_to_profile(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/profile');
    }

    public function test_agency_user_is_redirected_to_agency_dashboard(): void
    {
        $user = User::factory()->agency()->create(['password' => 'password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/agency');
    }

    public function test_admin_user_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->admin()->create(['password' => 'password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/admin');
    }

    public function test_intended_url_takes_precedence_over_role_fallback(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password']);

        $this->withSession(['url.intended' => '/listings'])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/listings');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/');
    }

    public function test_logout_clears_the_session_and_redirects_home(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_guest_cannot_logout(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }
}
