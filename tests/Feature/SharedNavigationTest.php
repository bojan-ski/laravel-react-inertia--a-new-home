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

    public function test_landmark_and_skip_link_labels_are_shared_to_guests(): void
    {
        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('nav.aria_label', __('app.nav.aria_label'))
                ->where('nav.skip_to_content', __('app.nav.skip_to_content'))
                ->where('footer.aria_label', __('app.footer.aria_label')));
    }

    public function test_row_action_label_template_is_shared_to_guests(): void
    {
        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('ui.action_for', __('app.ui.action_for'))
                ->where('ui.action_for', fn (string $template) => str_contains($template, ':action')
                    && str_contains($template, ':item')));
    }

    public function test_password_visibility_labels_are_shared_to_guests(): void
    {
        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->where('ui.show_password', __('app.ui.show_password'))
                ->where('ui.hide_password', __('app.ui.hide_password')));
    }

    public function test_account_tabs_are_shared_to_signed_in_users(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/bookmarks')
            ->assertInertia(fn (Assert $page) => $page
                ->where('accountTabs', __('app.account_tabs'))
                ->where('accountTabs.my_listings', 'My Listings')
                ->where('accountTabs.agency_profile', 'Agency Profile')
                ->where('accountTabs.label', 'Account'));
    }

    public function test_account_tabs_are_not_shared_to_guests(): void
    {
        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('accountTabs', null));
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
