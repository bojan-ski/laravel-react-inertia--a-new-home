<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_role_is_cast_to_user_role_enum(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertSame(UserRole::Admin, $user->role);
    }

    public function test_status_is_cast_to_user_status_enum(): void
    {
        $user = User::factory()->suspended()->create();

        $this->assertInstanceOf(UserStatus::class, $user->status);
        $this->assertSame(UserStatus::Suspended, $user->status);
    }

    public function test_suspended_at_is_cast_to_carbon(): void
    {
        $user = User::factory()->suspended()->create();

        $this->assertInstanceOf(Carbon::class, $user->suspended_at);
    }

    public function test_suspended_at_is_null_for_active_user(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->suspended_at);
    }

    public function test_new_user_defaults_to_regular_user_and_active(): void
    {
        $user = new User;

        $this->assertSame(UserRole::RegularUser, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_created_user_without_role_or_status_is_regular_and_active(): void
    {
        $user = User::factory()->create()->fresh();

        $this->assertSame(UserRole::RegularUser, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_is_regular_user_predicate(): void
    {
        $this->assertTrue(User::factory()->regularUser()->create()->isRegularUser());
        $this->assertFalse(User::factory()->admin()->create()->isRegularUser());
    }

    public function test_is_agency_predicate(): void
    {
        $this->assertTrue(User::factory()->agency()->create()->isAgency());
        $this->assertFalse(User::factory()->admin()->create()->isAgency());
    }

    public function test_is_admin_predicate(): void
    {
        $this->assertTrue(User::factory()->admin()->create()->isAdmin());
        $this->assertFalse(User::factory()->regularUser()->create()->isAdmin());
    }

    public function test_is_active_predicate(): void
    {
        $this->assertTrue(User::factory()->create()->isActive());
        $this->assertFalse(User::factory()->suspended()->create()->isActive());
    }

    public function test_is_suspended_predicate(): void
    {
        $this->assertTrue(User::factory()->suspended()->create()->isSuspended());
        $this->assertFalse(User::factory()->create()->isSuspended());
    }

    public function test_suspended_factory_state_sets_suspended_at(): void
    {
        $user = User::factory()->suspended()->create();

        $this->assertTrue($user->isSuspended());
        $this->assertNotNull($user->suspended_at);
    }
}
