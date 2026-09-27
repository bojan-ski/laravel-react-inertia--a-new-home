<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencyAccountSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array{User, Agency}
     */
    private function agencyOwner(): array
    {
        $user = User::factory()->agency()->create(['password' => 'password']);
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        return [$user, $agency];
    }

    public function test_agency_can_view_account_settings(): void
    {
        [$user] = $this->agencyOwner();

        $this->actingAs($user)
            ->get('/agency/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Settings')
                ->where('backHref', '/agency')
                ->where('passwordUrl', '/agency/settings/password')
                ->where('deleteUrl', '/agency/account')
                ->where('translations.back', __('app.agency_settings.back'))
                ->where('translations.delete.note', __('app.agency_settings.delete.note'))
                ->where('translations.delete.heading', __('app.profile_settings.delete.heading'))
                ->where('translations.password.heading', __('app.profile_settings.password.heading')));
    }

    public function test_regular_user_settings_keep_their_own_links(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/profile/settings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('backHref', '/profile')
                ->where('passwordUrl', '/profile/settings/password')
                ->where('deleteUrl', '/profile'));
    }

    public function test_agency_without_a_profile_can_still_open_settings(): void
    {
        $this->actingAs(User::factory()->agency()->create())
            ->get('/agency/settings')
            ->assertOk();
    }

    public function test_agency_can_change_password(): void
    {
        [$user] = $this->agencyOwner();

        $this->actingAs($user)
            ->put('/agency/settings/password', [
                'current_password' => 'password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertRedirect('/agency/settings')
            ->assertSessionHas('success', __('app.profile_settings.password.flash'));

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        [$user] = $this->agencyOwner();

        $this->actingAs($user)
            ->put('/agency/settings/password', [
                'current_password' => 'wrong',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertSessionHasErrors('current_password');
    }

    public function test_agency_can_delete_its_account(): void
    {
        Notification::fake();
        [$user, $agency] = $this->agencyOwner();
        $listing = Listing::factory()->approved()->create(['user_id' => null, 'agency_id' => $agency->id]);

        $this->actingAs($user)
            ->delete('/agency/account', ['password' => 'password'])
            ->assertRedirect('/')
            ->assertSessionHas('success', __('app.profile_settings.delete.flash'));

        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertModelMissing($agency);
        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        Notification::assertSentOnDemand(AccountDeletedNotification::class);
    }

    public function test_agency_deletion_requires_the_correct_password(): void
    {
        [$user, $agency] = $this->agencyOwner();

        $this->actingAs($user)
            ->delete('/agency/account', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->assertModelExists($user);
        $this->assertModelExists($agency);
    }

    public function test_other_roles_cannot_use_agency_settings(): void
    {
        $this->get('/agency/settings')->assertRedirect('/login');

        foreach ([User::factory()->regularUser()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get('/agency/settings')->assertForbidden();
            $this->actingAs($user)->delete('/agency/account', ['password' => 'password'])->assertForbidden();
            $this->assertModelExists($user);
        }
    }

    public function test_agency_dashboard_links_to_settings(): void
    {
        [$user] = $this->agencyOwner();

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page->where('translations.settings', __('app.agency_show.settings')));
    }
}
