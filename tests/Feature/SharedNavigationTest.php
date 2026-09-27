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

    public function test_both_navbars_are_shared_to_guests(): void
    {
        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('nav', __('app.nav'))
                ->where('listingsNav', __('app.listings.nav')));
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
    public function test_regular_user_pages_receive_listings_navbar(string $url, string $component): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->where('listingsNav', __('app.listings.nav')));
    }

    public function test_agency_pages_receive_listings_navbar(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->get('/agency/setup')
            ->assertInertia(fn (Assert $page) => $page->where('listingsNav', __('app.listings.nav')));

        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user->refresh())
            ->get('/agency/edit')
            ->assertInertia(fn (Assert $page) => $page->where('listingsNav', __('app.listings.nav')));
    }
}
