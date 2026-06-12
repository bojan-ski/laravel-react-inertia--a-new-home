<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'role:admin'])->get('/_test/admin', fn () => 'ok');
        Route::middleware(['web', 'role:admin,agency'])->get('/_test/admin-or-agency', fn () => 'ok');
    }

    public function test_user_with_allowed_role_passes_through(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/_test/admin')
            ->assertOk();
    }

    public function test_user_with_disallowed_role_gets_403(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->get('/_test/admin')
            ->assertForbidden();
    }

    public function test_multiple_allowed_roles_are_supported(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/_test/admin-or-agency')
            ->assertOk();

        $this->actingAs(User::factory()->agency()->create())
            ->get('/_test/admin-or-agency')
            ->assertOk();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/_test/admin-or-agency')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/_test/admin')
            ->assertRedirect('/login');
    }

    public function test_suspended_user_is_logged_out_and_redirected(): void
    {
        $admin = User::factory()->admin()->suspended()->create();

        $this->actingAs($admin)
            ->get('/_test/admin')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->get('/_test/admin')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
