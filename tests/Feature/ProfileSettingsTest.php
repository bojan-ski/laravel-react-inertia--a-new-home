<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_regular_user_can_view_the_settings_page(): void
    {
        $user = User::factory()->regularUser()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $this->actingAs($user)
            ->get('/profile/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Settings')
                ->where('user.name', 'Jane Doe')
                ->where('user.email', 'jane@example.com')
            );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profile/settings')->assertRedirect('/login');
        $this->put('/profile/settings/account')->assertRedirect('/login');
        $this->put('/profile/settings/password')->assertRedirect('/login');
    }

    public function test_agency_user_cannot_access(): void
    {
        $agency = User::factory()->agency()->create();

        $this->actingAs($agency)->get('/profile/settings')->assertForbidden();
        $this->actingAs($agency)->put('/profile/settings/account')->assertForbidden();
        $this->actingAs($agency)->put('/profile/settings/password')->assertForbidden();
    }

    public function test_admin_user_cannot_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/profile/settings')->assertForbidden();
        $this->actingAs($admin)->put('/profile/settings/account')->assertForbidden();
        $this->actingAs($admin)->put('/profile/settings/password')->assertForbidden();
    }

    public function test_regular_user_can_update_their_name(): void
    {
        $user = User::factory()->regularUser()->create(['name' => 'Old Name', 'email' => 'keep@example.com']);

        $this->actingAs($user)
            ->put('/profile/settings/account', ['name' => 'New Name', 'email' => 'keep@example.com'])
            ->assertRedirect('/profile/settings')
            ->assertSessionHas('success', 'Account details updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'keep@example.com',
        ]);
    }

    public function test_regular_user_can_update_their_email(): void
    {
        $user = User::factory()->regularUser()->create(['name' => 'Same Name', 'email' => 'old@example.com']);

        $this->actingAs($user)
            ->put('/profile/settings/account', ['name' => 'Same Name', 'email' => 'new@example.com'])
            ->assertRedirect('/profile/settings')
            ->assertSessionHas('success', 'Account details updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Same Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_email_must_be_unique_across_other_users(): void
    {
        $user = User::factory()->regularUser()->create(['email' => 'mine@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user)
            ->put('/profile/settings/account', ['name' => 'Whatever', 'email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'mine@example.com']);
    }

    public function test_email_staying_the_same_passes_validation(): void
    {
        $user = User::factory()->regularUser()->create(['email' => 'self@example.com']);

        $this->actingAs($user)
            ->put('/profile/settings/account', ['name' => 'Updated Name', 'email' => 'self@example.com'])
            ->assertRedirect('/profile/settings')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'self@example.com',
        ]);
    }

    public function test_regular_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password']);

        $this->actingAs($user)
            ->put('/profile/settings/password', [
                'current_password' => 'password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertRedirect('/profile/settings')
            ->assertSessionHas('success', 'Password updated.');

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password']);

        $this->actingAs($user)
            ->put('/profile/settings/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_change_fails_when_new_password_is_too_short(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password']);

        $this->actingAs($user)
            ->put('/profile/settings/password', [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_change_fails_when_confirmation_does_not_match(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password']);

        $this->actingAs($user)
            ->put('/profile/settings/password', [
                'current_password' => 'password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_password_is_stored_hashed_not_plaintext(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password']);

        $this->actingAs($user)->put('/profile/settings/password', [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $stored = $user->fresh()->password;

        $this->assertNotSame('new-secret-password', $stored);
        $this->assertTrue(Hash::check('new-secret-password', $stored));
    }

    public function test_account_update_does_not_mutate_protected_fields(): void
    {
        $user = User::factory()->regularUser()->create();
        $originalRole = $user->role;
        $originalStatus = $user->status;
        $originalSuspendedAt = $user->suspended_at;
        $originalRememberToken = $user->remember_token;
        $originalCreatedAt = $user->created_at;

        $this->actingAs($user)->put('/profile/settings/account', [
            'name' => 'Renamed',
            'email' => 'renamed@example.com',
        ]);

        $fresh = $user->fresh();

        $this->assertSame($originalRole, $fresh->role);
        $this->assertSame($originalStatus, $fresh->status);
        $this->assertEquals($originalSuspendedAt, $fresh->suspended_at);
        $this->assertSame($originalRememberToken, $fresh->remember_token);
        $this->assertEquals($originalCreatedAt, $fresh->created_at);
        $this->assertSame(UserRole::RegularUser, $fresh->role);
        $this->assertSame(UserStatus::Active, $fresh->status);
    }

    public function test_password_update_does_not_mutate_protected_fields(): void
    {
        $user = User::factory()->regularUser()->create(['password' => 'password']);
        $originalRole = $user->role;
        $originalStatus = $user->status;
        $originalSuspendedAt = $user->suspended_at;
        $originalName = $user->name;
        $originalEmail = $user->email;
        $originalCreatedAt = $user->created_at;

        $this->actingAs($user)->put('/profile/settings/password', [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $fresh = $user->fresh();

        $this->assertSame($originalRole, $fresh->role);
        $this->assertSame($originalStatus, $fresh->status);
        $this->assertEquals($originalSuspendedAt, $fresh->suspended_at);
        $this->assertSame($originalName, $fresh->name);
        $this->assertSame($originalEmail, $fresh->email);
        $this->assertEquals($originalCreatedAt, $fresh->created_at);
    }
}
