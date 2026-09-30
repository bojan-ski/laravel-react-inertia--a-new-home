<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SharedNavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_one_navbar_is_shared_to_guests(): void
    {
        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('nav', __('app.nav'))
                ->where('nav.agencies', __('app.nav.agencies'))
                ->where('nav.favorites', __('app.nav.favorites'))
                ->missing('listingsNav'));
    }

    public function test_dismiss_label_is_shared_to_guests(): void
    {
        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('ui.dismiss', __('app.ui.dismiss')));
    }

    public function test_password_visibility_labels_are_shared_to_guests(): void
    {
        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->where('ui.show_password', __('app.ui.show_password'))
                ->where('ui.hide_password', __('app.ui.hide_password')));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function regularUserPages(): array
    {
        return [
            'dashboard' => ['/profile', 'Profile/Index'],
            'account settings' => ['/profile/settings', 'Profile/Settings'],
            'create listing' => ['/my-listings/create', 'Listings/Create'],
            'conversations' => ['/conversations', 'Conversations/Index'],
        ];
    }

    #[DataProvider('regularUserPages')]
    public function test_regular_user_pages_receive_the_shared_navbar(string $url, string $component): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->where('nav', __('app.nav'))
                ->missing('listingsNav'));
    }

    public function test_agency_pages_receive_the_shared_navbar(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->get('/agency/setup')
            ->assertInertia(fn (Assert $page) => $page
                ->where('nav', __('app.nav'))
                ->missing('listingsNav'));

        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user->refresh())
            ->get('/agency/edit')
            ->assertInertia(fn (Assert $page) => $page
                ->where('nav', __('app.nav'))
                ->missing('listingsNav'));
    }

    public function test_admin_pages_receive_the_shared_navbar(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Listings/Index')
                ->where('nav', __('app.nav'))
                ->missing('listingsNav'));
    }
}
