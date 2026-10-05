<?php

namespace Tests\Feature\System;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmptyInstallTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Page => [owning actor, component, list props that must be empty, empty-state key under translations].
     *
     * @var array<string, array{0: string, 1: ?string, 2: list<string>, 3: ?string}>
     */
    private const PAGES = [
        'home' => ['guest', 'Home', ['latestListings'], null],
        'pages.about' => ['guest', 'Static/Show', [], null],
        'pages.privacy' => ['guest', 'Static/Show', [], null],
        'pages.terms' => ['guest', 'Static/Show', [], null],
        'pages.cookies' => ['guest', 'Static/Show', [], null],
        'contact.show' => ['guest', 'Contact/Show', [], null],
        'listings.index' => ['guest', 'Listings/Index', ['listings.data', 'searchSummary'], 'empty.headline'],
        'agencies.index' => ['guest', 'Agencies/Index', [], 'empty.none'],
        'robots' => ['guest', null, [], null],
        'sitemap' => ['guest', null, [], null],
        'login' => ['guest', 'Auth/Login', [], null],
        'register' => ['guest', 'Auth/Register', [], null],
        'password.request' => ['guest', 'Auth/ForgotPassword', [], null],
        'bookmarks.index' => ['regular', 'Bookmarks/Index', ['listings', 'closedListings'], 'empty.headline'],
        'saved-searches.index' => ['regular', 'SavedSearches/Index', ['searches'], 'empty.headline'],
        'conversations.index' => ['regular', 'Conversations/Index', ['conversations'], 'empty.headline'],
        'profile.index' => ['regular', 'Profile/Index', ['listings'], 'empty.headline'],
        'profile.settings.edit' => ['regular', 'Profile/Settings', [], null],
        'my-listings.create' => ['regular', 'Listings/Create', [], null],
        'agency.setup' => ['agency', null, [], null],
        'agency.index' => ['agency', 'Agency/Show', ['listings'], 'listings.empty_headline_approved'],
        'agency.edit' => ['agency', 'Agency/Edit', [], null],
        'agency.settings.edit' => ['agency', 'Profile/Settings', [], null],
        'agency.listings.create' => ['agency', 'Agency/Listings/Create', [], null],
        'admin.dashboard' => ['admin', 'Admin/Dashboard', [], null],
        'admin.listings.index' => ['admin', 'Admin/Listings/Index', ['listings.data'], 'empty.title'],
        'admin.agencies.index' => ['admin', 'Admin/Agencies/Index', ['agencies.data', 'profileChanges'], 'empty.title'],
        'admin.users.index' => ['admin', 'Admin/Users/Index', [], 'empty.title'],
        'admin.listing-reports.index' => ['admin', 'Admin/ListingReports/Index', ['listings.data'], 'empty.title'],
        'admin.reported-messages.index' => ['admin', 'Admin/ReportedMessages/Index', ['reports.data'], 'empty.title'],
        'admin.contact-messages.index' => ['admin', 'Admin/ContactMessages/Index', ['messages.data'], 'empty.new.title'],
    ];

    /**
     * @var array<string, User>
     */
    private array $accounts = [];

    /**
     * @return array<string, array{0: string}>
     */
    public static function pages(): array
    {
        return collect(array_keys(self::PAGES))->mapWithKeys(fn (string $name): array => [$name => [$name]])->all();
    }

    public function test_every_page_without_a_route_parameter_is_listed(): void
    {
        $pages = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route): bool => in_array('GET', $route->methods(), true)
                && $route->parameterNames() === []
                && str_starts_with((string) $route->getActionName(), 'App\\'))
            ->map(fn (RoutingRoute $route): ?string => $route->getName())
            ->sort()
            ->values()
            ->all();

        $listed = array_keys(self::PAGES);
        sort($listed);

        $this->assertSame($listed, $pages);
    }

    #[DataProvider('pages')]
    public function test_page_renders_on_an_empty_install_for_its_owner(string $name): void
    {
        [$actor, $component, $emptyLists, $emptyKey] = self::PAGES[$name];

        $this->seedOneAccountPerRole();

        if ($actor !== 'guest') {
            $this->actingAs($this->accounts[$actor]);
        }

        $response = $this->get(route($name));

        if ($name === 'agency.setup') {
            $response->assertRedirect(route('agency.index'));

            return;
        }

        $response->assertOk();

        if ($component === null) {
            return;
        }

        $response->assertInertia(function (Assert $page) use ($component, $emptyLists, $emptyKey) {
            $page->component($component);

            foreach ($emptyLists as $path) {
                $page->has($path, 0);
            }

            if ($emptyKey !== null) {
                $page->where("translations.{$emptyKey}", fn (mixed $value): bool => is_string($value) && $value !== '');
            }
        });
    }

    public function test_the_only_rows_are_the_accounts_and_the_agency_profile(): void
    {
        $this->seedOneAccountPerRole();

        $this->assertSame(3, User::count());
        $this->assertSame(1, Agency::count());
        $this->assertSame([], Arr::except($this->rowCounts(), ['users', 'agencies', 'migrations']));
    }

    private function seedOneAccountPerRole(): void
    {
        $this->accounts = [
            'regular' => User::factory()->regularUser()->create(),
            'agency' => User::factory()->agency()->create(),
            'admin' => User::factory()->admin()->create(),
        ];

        Agency::factory()->approved()->create(['user_id' => $this->accounts['agency']->id]);
    }

    /**
     * @return array<string, int>
     */
    private function rowCounts(): array
    {
        $connection = $this->app['db']->connection();

        return collect($this->app['db']->connection()->getSchemaBuilder()->getTableListing($connection->getDatabaseName(), false))
            ->mapWithKeys(fn (string $table): array => [$table => $connection->table($table)->count()])
            ->filter()
            ->all();
    }
}
