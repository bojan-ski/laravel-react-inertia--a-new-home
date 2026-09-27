<?php

namespace Tests\Feature;

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
                ->missing('user')
                ->where('translations.delete.heading', __('app.profile_settings.delete.heading'))
                ->missing('translations.account')
            );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profile/settings')->assertRedirect('/login');
        $this->put('/profile/settings/password')->assertRedirect('/login');
        $this->delete('/profile')->assertRedirect('/login');
    }

    public function test_agency_user_cannot_access(): void
    {
        $agency = User::factory()->agency()->create();

        $this->actingAs($agency)->get('/profile/settings')->assertForbidden();
        $this->actingAs($agency)->put('/profile/settings/password')->assertForbidden();
        $this->actingAs($agency)->delete('/profile', ['password' => 'password'])->assertForbidden();
    }

    public function test_admin_user_cannot_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/profile/settings')->assertForbidden();
        $this->actingAs($admin)->put('/profile/settings/password')->assertForbidden();
        $this->actingAs($admin)->delete('/profile', ['password' => 'password'])->assertForbidden();
    }

    public function test_name_and_email_can_no_longer_be_changed(): void
    {
        $user = User::factory()->regularUser()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $this->actingAs($user)
            ->put('/profile/settings/account', ['name' => 'New Name', 'email' => 'new@example.com'])
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Jane Doe', 'email' => 'jane@example.com']);
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
