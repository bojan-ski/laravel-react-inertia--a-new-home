<?php

namespace Tests\Feature\System;

use App\Enums\ListingReportReason;
use App\Models\Agency;
use App\Models\AgencyProfileChange;
use App\Models\ChatMessage;
use App\Models\ContactMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingReport;
use App\Models\ReportedChatMessage;
use App\Models\SavedSearch;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionFunction;
use Tests\TestCase;

class RouteAuthorizationMatrixTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const ROLES = ['regular', 'agency', 'admin'];

    /**
     * Route => [access group, suspended outcome, right roles when narrower than the group].
     *
     * @var array<string, array{0: string, 1: string, 2?: list<string>}>
     */
    private const ACCESS = [
        'home' => ['public', 'as_signed_in'],
        'pages.about' => ['public', 'as_signed_in'],
        'pages.privacy' => ['public', 'as_signed_in'],
        'pages.terms' => ['public', 'as_signed_in'],
        'pages.cookies' => ['public', 'as_signed_in'],
        'contact.show' => ['public', 'as_signed_in'],
        'contact.store' => ['public', 'as_signed_in'],
        'listings.index' => ['public', 'as_signed_in'],
        'agencies.index' => ['public', 'as_signed_in'],
        'agencies.show' => ['public', 'as_signed_in'],
        'robots' => ['public', 'as_signed_in'],
        'sitemap' => ['public', 'as_signed_in'],

        'login' => ['guest', 'as_signed_in'],
        'POST login' => ['guest', 'as_signed_in'],
        'register' => ['guest', 'as_signed_in'],
        'POST register' => ['guest', 'as_signed_in'],
        'password.request' => ['guest', 'as_signed_in'],
        'password.email' => ['guest', 'as_signed_in'],
        'password.reset' => ['guest', 'as_signed_in'],
        'password.update' => ['guest', 'as_signed_in'],

        'saved-searches.unsubscribe.show' => ['signed', 'as_signed_in'],
        'saved-searches.unsubscribe' => ['signed', 'as_signed_in'],

        'listings.show' => ['auth', 'signed_out'],
        'listings.bookmark' => ['auth', 'forbidden', ['regular', 'agency']],
        'listings.report' => ['auth', 'signed_out', ['regular', 'agency']],
        'bookmarks.index' => ['auth', 'signed_out'],
        'conversations.index' => ['auth', 'signed_out'],
        'conversations.show' => ['auth', 'forbidden'],
        'listings.conversations.store' => ['auth', 'forbidden'],
        'conversations.messages.store' => ['auth', 'forbidden'],
        'messages.report' => ['auth', 'refused'],
        'saved-searches.index' => ['auth', 'signed_out', ['regular', 'agency']],
        'saved-searches.store' => ['auth', 'signed_out', ['regular', 'agency']],
        'saved-searches.update' => ['auth', 'signed_out', ['regular', 'agency']],
        'saved-searches.destroy' => ['auth', 'signed_out', ['regular', 'agency']],
        'logout' => ['auth', 'as_signed_in'],

        'profile.index' => ['regular_user', 'signed_out'],
        'profile.settings.edit' => ['regular_user', 'signed_out'],
        'profile.settings.password.update' => ['regular_user', 'signed_out'],
        'profile.destroy' => ['regular_user', 'signed_out'],
        'my-listings.create' => ['regular_user', 'signed_out'],
        'my-listings.store' => ['regular_user', 'signed_out'],
        'my-listings.edit' => ['regular_user', 'signed_out'],
        'my-listings.update' => ['regular_user', 'signed_out'],
        'my-listings.destroy' => ['regular_user', 'signed_out'],
        'my-listings.close' => ['regular_user', 'signed_out'],
        'my-listings.reopen' => ['regular_user', 'signed_out'],
        'my-listings.renew' => ['regular_user', 'signed_out'],
        'my-listings.images.store' => ['regular_user', 'signed_out'],
        'my-listings.images.destroy' => ['regular_user', 'signed_out'],

        'agency.setup' => ['agency', 'signed_out'],
        'agency.setup.store' => ['agency', 'signed_out'],
        'agency.settings.edit' => ['agency', 'signed_out'],
        'agency.settings.password.update' => ['agency', 'signed_out'],
        'agency.account.destroy' => ['agency', 'signed_out'],
        'agency.index' => ['agency', 'signed_out'],
        'agency.edit' => ['agency', 'signed_out'],
        'agency.update' => ['agency', 'signed_out'],
        'agency.listings.create' => ['agency', 'signed_out'],
        'agency.listings.store' => ['agency', 'signed_out'],
        'agency.listings.edit' => ['agency', 'signed_out'],
        'agency.listings.update' => ['agency', 'signed_out'],
        'agency.listings.destroy' => ['agency', 'signed_out'],
        'agency.listings.close' => ['agency', 'signed_out'],
        'agency.listings.reopen' => ['agency', 'signed_out'],
        'agency.listings.renew' => ['agency', 'signed_out'],
        'agency.listings.images.store' => ['agency', 'signed_out'],
        'agency.listings.images.destroy' => ['agency', 'signed_out'],

        'admin.dashboard' => ['admin', 'signed_out'],
        'admin.listings.index' => ['admin', 'signed_out'],
        'admin.listings.show' => ['admin', 'signed_out'],
        'admin.listings.approve' => ['admin', 'signed_out'],
        'admin.listings.reject' => ['admin', 'signed_out'],
        'admin.agencies.index' => ['admin', 'signed_out'],
        'admin.agencies.approve' => ['admin', 'signed_out'],
        'admin.agencies.reject' => ['admin', 'signed_out'],
        'admin.agencies.changes.approve' => ['admin', 'signed_out'],
        'admin.agencies.changes.reject' => ['admin', 'signed_out'],
        'admin.users.index' => ['admin', 'signed_out'],
        'admin.users.suspend' => ['admin', 'signed_out'],
        'admin.users.reactivate' => ['admin', 'signed_out'],
        'admin.users.destroy' => ['admin', 'signed_out'],
        'admin.listing-reports.index' => ['admin', 'signed_out'],
        'admin.listing-reports.dismiss' => ['admin', 'signed_out'],
        'admin.listing-reports.take-down' => ['admin', 'signed_out'],
        'admin.reported-messages.index' => ['admin', 'signed_out'],
        'admin.reported-messages.resolve' => ['admin', 'signed_out'],
        'admin.reported-messages.suspend' => ['admin', 'signed_out'],
        'admin.contact-messages.index' => ['admin', 'signed_out'],
        'admin.contact-messages.handled' => ['admin', 'signed_out'],
        'admin.contact-messages.destroy' => ['admin', 'signed_out'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const GROUP_RIGHT_ROLES = [
        'public' => self::ROLES,
        'guest' => [],
        'signed' => [],
        'auth' => self::ROLES,
        'regular_user' => ['regular'],
        'agency' => ['agency'],
        'admin' => ['admin'],
    ];

    /**
     * @var array<string, string>
     */
    private const GROUP_SUSPENDED_ACTOR = [
        'public' => 'suspended_regular',
        'guest' => 'suspended_regular',
        'signed' => 'suspended_regular',
        'auth' => 'suspended_regular',
        'regular_user' => 'suspended_regular',
        'agency' => 'suspended_agency',
        'admin' => 'suspended_admin',
    ];

    /**
     * @var array<string, mixed>
     */
    private array $fx = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function routes(): array
    {
        return collect(array_keys(self::ACCESS))
            ->mapWithKeys(fn (string $key): array => [$key => [$key]])
            ->all();
    }

    public function test_every_application_route_has_a_row_and_every_row_is_a_route(): void
    {
        $routeKeys = collect(Route::getRoutes()->getRoutes())
            ->reject(fn (RoutingRoute $route): bool => $this->isVendorRoute($route))
            ->map(fn (RoutingRoute $route): string => $this->routeKey($route))
            ->values()
            ->all();

        $rows = array_keys(self::ACCESS);

        $this->assertSame([], array_values(array_diff($routeKeys, $rows)), 'Routes without a row in the matrix.');
        $this->assertSame([], array_values(array_diff($rows, $routeKeys)), 'Matrix rows without a route.');
        $this->assertSame(count($routeKeys), count(array_unique($routeKeys)), 'Duplicate route keys.');
    }

    #[DataProvider('routes')]
    public function test_route_answers_each_actor_as_its_access_group(string $key): void
    {
        Notification::fake();
        Storage::fake('public');

        $group = self::ACCESS[$key][0];
        $suspendedOutcome = self::ACCESS[$key][1];
        $rightRoles = self::ACCESS[$key][2] ?? self::GROUP_RIGHT_ROLES[$group];
        $route = $this->resolveRoute($key);

        $this->buildFixtures();

        if ($group === 'signed') {
            $this->assertSignedRoute($key, $route);

            return;
        }

        $this->assertGuestCell($key, $route, $group);
        $this->assertWrongRoleCells($key, $route, $group, $rightRoles);
        $this->assertSuspendedCell($key, $route, $group, $suspendedOutcome);
        $this->assertRightRoleCells($key, $route, $rightRoles);
    }

    private function assertGuestCell(string $key, RoutingRoute $route, string $group): void
    {
        $this->resetActor();
        $cell = "{$key} as guest";

        if (in_array($group, ['public', 'guest'], true)) {
            $this->assertReached($this->send($key, $route, 'regular'), $cell);

            return;
        }

        $response = $this->withUnchangedDatabase(fn (): TestResponse => $this->send($key, $route, $this->ownerFor($key)), $cell);

        $this->assertSame(url('/login'), $response->headers->get('Location'), "{$cell} must be sent to /login, got {$response->getStatusCode()}.");
    }

    /**
     * @param  list<string>  $rightRoles
     */
    private function assertWrongRoleCells(string $key, RoutingRoute $route, string $group, array $rightRoles): void
    {
        if ($group === 'public') {
            return;
        }

        foreach (array_diff(self::ROLES, $rightRoles) as $role) {
            $this->resetActor();
            $this->actingAs($this->fx['user'][$role]);
            $cell = "{$key} as wrong role {$role}";

            $response = $this->withUnchangedDatabase(fn (): TestResponse => $this->send($key, $route, $this->ownerFor($key)), $cell);

            if ($group === 'guest') {
                $this->assertTrue($response->isRedirect(), "{$cell} must be redirected away, got {$response->getStatusCode()}.");

                continue;
            }

            $this->assertSame(403, $response->getStatusCode(), "{$cell} must be 403, got {$response->getStatusCode()}.");
        }
    }

    private function assertSuspendedCell(string $key, RoutingRoute $route, string $group, string $outcome): void
    {
        $actor = self::GROUP_SUSPENDED_ACTOR[$group];
        $user = $this->fx['user'][$actor];
        $cell = "{$key} as {$actor}";

        $this->resetActor();
        $this->actingAs($user);

        if ($key === 'contact.store') {
            $before = $this->databaseSnapshot(except: ['contact_messages']);
            $count = ContactMessage::count();

            $this->assertReached($this->send($key, $route, $actor), $cell);

            $this->assertSame($before, $this->databaseSnapshot(except: ['contact_messages']), "{$cell} changed a table other than contact_messages.");
            $this->assertSame($count + 1, ContactMessage::count(), "{$cell} must store the message (D40-2).");
            $this->assertSame($user->id, ContactMessage::latest('id')->first()->user_id, "{$cell} must link the message to the account.");

            return;
        }

        $response = $this->withUnchangedDatabase(fn (): TestResponse => $this->send($key, $route, $actor), $cell, refused: $outcome !== 'as_signed_in');

        match ($outcome) {
            'signed_out' => $this->assertSignedOut($response, $cell),
            'forbidden' => $this->assertForbiddenAndSignedIn($response, $user, $cell),
            'refused' => $this->assertRefusedAndSignedIn($response, $user, $cell),
            'as_signed_in' => $group === 'guest'
                ? $this->assertTrue($response->isRedirect(), "{$cell} must be redirected away, got {$response->getStatusCode()}.")
                : $this->assertReached($response, $cell),
        };
    }

    /**
     * @param  list<string>  $rightRoles
     */
    private function assertRightRoleCells(string $key, RoutingRoute $route, array $rightRoles): void
    {
        foreach ($rightRoles as $role) {
            $this->resetActor();
            $this->actingAs($this->fx['user'][$role]);

            $this->assertReached($this->send($key, $route, $role), "{$key} as right role {$role}");
        }
    }

    private function assertSignedRoute(string $key, RoutingRoute $route): void
    {
        $this->resetActor();
        $unsigned = $this->withUnchangedDatabase(fn (): TestResponse => $this->send($key, $route, 'regular'), "{$key} unsigned as guest");
        $this->assertSame(403, $unsigned->getStatusCode(), "{$key} unsigned as guest must be 403.");

        foreach ([...self::ROLES, 'suspended_regular'] as $actor) {
            $this->resetActor();
            $this->actingAs($this->fx['user'][$actor]);
            $cell = "{$key} unsigned as {$actor}";

            $response = $this->withUnchangedDatabase(fn (): TestResponse => $this->send($key, $route, 'regular'), $cell);

            $this->assertSame(403, $response->getStatusCode(), "{$cell} must be 403.");
        }

        $this->resetActor();
        $this->assertReached($this->send($key, $route, 'regular', signed: true), "{$key} signed as guest");
    }

    private function assertReached(TestResponse $response, string $cell): void
    {
        $status = $response->getStatusCode();

        $this->assertNotContains($status, [401, 403, 404, 405, 419], "{$cell} must reach the action, got {$status}.");
        $this->assertLessThan(500, $status, "{$cell} must reach the action, got {$status}.");
        $this->assertNotSame(url('/login'), $response->headers->get('Location'), "{$cell} must not be sent to /login.");
    }

    private function assertSignedOut(TestResponse $response, string $cell): void
    {
        $this->assertSame(url('/login'), $response->headers->get('Location'), "{$cell} must be sent to /login, got {$response->getStatusCode()}.");
        $this->assertFalse($this->app['auth']->guard('web')->check(), "{$cell} must be signed out.");
        $response->assertSessionHasErrors(['email' => __('auth.suspended')]);
    }

    private function assertForbiddenAndSignedIn(TestResponse $response, User $user, string $cell): void
    {
        $this->assertSame(403, $response->getStatusCode(), "{$cell} must be 403, got {$response->getStatusCode()}.");
        $this->assertSame($user->id, $this->app['auth']->guard('web')->id(), "{$cell} must stay signed in (D40-5).");
    }

    private function assertRefusedAndSignedIn(TestResponse $response, User $user, string $cell): void
    {
        $this->assertTrue($response->isRedirect(), "{$cell} must be redirected back, got {$response->getStatusCode()}.");
        $this->assertSame(__('app.reports.errors.suspended'), session('error'), "{$cell} must carry the refusal.");
        $this->assertSame($user->id, $this->app['auth']->guard('web')->id(), "{$cell} must stay signed in (D40-5).");
    }

    /**
     * @param  Closure(): TestResponse  $request
     */
    private function withUnchangedDatabase(Closure $request, string $cell, bool $refused = true): TestResponse
    {
        $before = $this->databaseSnapshot();

        $response = $request();

        if ($refused) {
            $this->assertNotSame(200, $response->getStatusCode(), "{$cell} must not answer 200.");
        }

        $this->assertSame($before, $this->databaseSnapshot(), "{$cell} changed the database.");

        return $response;
    }

    /**
     * @param  list<string>  $except
     * @return array<string, list<string>>
     */
    private function databaseSnapshot(array $except = []): array
    {
        $tables = Schema::getTableListing(DB::connection()->getDatabaseName(), false);
        $snapshot = [];

        foreach (array_diff($tables, ['migrations', ...$except]) as $table) {
            $rows = DB::table($table)->get()
                ->map(fn (object $row): string => json_encode(collect((array) $row)->except('remember_token')->all()))
                ->sort()
                ->values()
                ->all();

            $snapshot[$table] = $rows;
        }

        ksort($snapshot);

        return $snapshot;
    }

    private function send(string $key, RoutingRoute $route, string $owner, bool $signed = false): TestResponse
    {
        $method = $route->methods()[0];
        $parameters = $this->parameters($key, $owner);

        $url = match (true) {
            $route->getName() === null => url($route->uri()),
            $signed => URL::signedRoute($route->getName(), $parameters),
            default => route($route->getName(), $parameters),
        };

        if ($method === 'GET') {
            return $this->get($url);
        }

        return $this->call($method, $url, $this->payload($key));
    }

    private function resetActor(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    private function ownerFor(string $key): string
    {
        $group = self::ACCESS[$key][0];

        return (self::ACCESS[$key][2] ?? self::GROUP_RIGHT_ROLES[$group])[0] ?? 'regular';
    }

    private function resolveRoute(string $key): RoutingRoute
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn (RoutingRoute $route): bool => $this->routeKey($route) === $key);

        $this->assertNotNull($route, "No route for matrix row {$key}.");

        return $route;
    }

    private function routeKey(RoutingRoute $route): string
    {
        return $route->getName() ?? $route->methods()[0].' '.$route->uri();
    }

    private function isVendorRoute(RoutingRoute $route): bool
    {
        $action = $route->getAction('uses');

        $file = match (true) {
            $action instanceof Closure => (new ReflectionFunction($action))->getFileName(),
            is_string($action) && class_exists(Str::before($action, '@')) => (new ReflectionClass(Str::before($action, '@')))->getFileName(),
            default => '',
        };

        return str_starts_with(str_replace('\\', '/', (string) $file), str_replace('\\', '/', base_path('vendor')));
    }

    /**
     * @return array<string, mixed>
     */
    private function parameters(string $key, string $owner): array
    {
        $fx = $this->fx;

        return match ($key) {
            'agencies.show' => ['agency' => $fx['agency']['agency']->slug],
            'password.reset' => ['token' => 'matrix-token'],
            'saved-searches.unsubscribe.show', 'saved-searches.unsubscribe' => ['user' => $fx['user']['regular']->id],
            'listings.show' => ['listing' => $fx['otherListing']->slug],
            'listings.bookmark', 'listings.report', 'listings.conversations.store' => ['listing' => $fx['otherListing']->id],
            'conversations.show', 'conversations.messages.store' => ['conversation' => $fx['conversation'][$owner]->id],
            'messages.report' => ['message' => $fx['message'][$owner]->id],
            'saved-searches.update', 'saved-searches.destroy' => ['savedSearch' => $fx['savedSearch'][$owner]->id],
            'my-listings.edit', 'my-listings.update', 'my-listings.destroy', 'my-listings.close', 'my-listings.renew', 'my-listings.images.store',
            'agency.listings.edit', 'agency.listings.update', 'agency.listings.destroy', 'agency.listings.close', 'agency.listings.renew', 'agency.listings.images.store' => ['listing' => $fx['listing'][$owner]->id],
            'my-listings.reopen', 'agency.listings.reopen' => ['listing' => $fx['sold'][$owner]->id],
            'my-listings.images.destroy', 'agency.listings.images.destroy' => ['listing' => $fx['listing'][$owner]->id, 'image' => $fx['image'][$owner]->id],
            'admin.listings.show', 'admin.listings.approve', 'admin.listings.reject' => ['listing' => $fx['pendingListing']->id],
            'admin.agencies.approve', 'admin.agencies.reject' => ['agency' => $fx['pendingAgency']->id],
            'admin.agencies.changes.approve', 'admin.agencies.changes.reject' => ['agency' => $fx['changedAgency']->id],
            'admin.users.suspend', 'admin.users.destroy' => ['user' => $fx['victim']->id],
            'admin.users.reactivate' => ['user' => $fx['suspendedVictim']->id],
            'admin.listing-reports.dismiss', 'admin.listing-reports.take-down' => ['listing' => $fx['reportedListing']->id],
            'admin.reported-messages.resolve', 'admin.reported-messages.suspend' => ['report' => $fx['chatReport']->id],
            'admin.contact-messages.handled', 'admin.contact-messages.destroy' => ['contactMessage' => $fx['contactMessage']->id],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $key): array
    {
        return match ($key) {
            'contact.store' => ['email' => 'matrix@example.test', 'subject' => 'Matrix subject', 'message' => 'A matrix message.'],
            'listings.report' => ['reason' => ListingReportReason::Scam->value],
            'listings.conversations.store' => ['message' => 'Is it still available?'],
            'conversations.messages.store' => ['body' => 'A matrix reply.'],
            'messages.report' => ['reason' => 'A matrix report reason.'],
            'saved-searches.store' => ['name' => 'Matrix search', 'listingType' => 'sale'],
            'saved-searches.update' => ['name' => 'Renamed matrix search'],
            'my-listings.store', 'my-listings.update', 'agency.listings.store', 'agency.listings.update' => [
                'title' => 'Matrix Apartment',
                'listing_type' => 'sale',
                'property_type' => 'apartment',
                'price' => 250000,
                'description' => 'A matrix listing description.',
                'country' => 'Serbia',
                'city' => 'Belgrade',
            ],
            'my-listings.images.store', 'agency.listings.images.store' => ['image' => UploadedFile::fake()->image('matrix.jpg', 800, 600)],
            'profile.settings.password.update', 'agency.settings.password.update' => [
                'current_password' => 'password',
                'password' => 'matrix-password-1',
                'password_confirmation' => 'matrix-password-1',
            ],
            'profile.destroy', 'agency.account.destroy' => ['password' => 'password'],
            'agency.setup.store', 'agency.update' => [
                'name' => 'Matrix Estates',
                'legal_name' => 'Matrix Estates Ltd.',
                'registration_number' => 'REG-12345678',
                'registration_country' => 'DE',
                'email' => 'office@matrix.test',
            ],
            'admin.listings.reject', 'admin.agencies.reject', 'admin.agencies.changes.reject', 'admin.listing-reports.take-down' => ['reason' => 'A matrix moderation reason.'],
            default => [],
        };
    }

    private function buildFixtures(): void
    {
        $users = [
            'regular' => User::factory()->regularUser()->create(),
            'agency' => User::factory()->agency()->create(),
            'admin' => User::factory()->admin()->create(),
            'suspended_regular' => User::factory()->regularUser()->suspended()->create(),
            'suspended_agency' => User::factory()->agency()->suspended()->create(),
            'suspended_admin' => User::factory()->admin()->suspended()->create(),
        ];

        $agencies = [
            'agency' => Agency::factory()->approved()->create(['user_id' => $users['agency']->id]),
            'suspended_agency' => Agency::factory()->approved()->create(['user_id' => $users['suspended_agency']->id]),
        ];

        $other = User::factory()->regularUser()->create();
        $otherListing = Listing::factory()->approved()->create(['user_id' => $other->id]);

        $listings = [];
        $sold = [];
        $images = [];

        foreach (['regular', 'agency', 'suspended_regular', 'suspended_agency'] as $key) {
            $ownedBy = isset($agencies[$key])
                ? ['user_id' => null, 'agency_id' => $agencies[$key]->id]
                : ['user_id' => $users[$key]->id, 'agency_id' => null];

            $listings[$key] = Listing::factory()->approved()->create([...$ownedBy, 'listing_type' => 'sale', 'expires_at' => now()->addDays(3)]);
            $sold[$key] = Listing::factory()->sold()->create($ownedBy);
            $images[$key] = ListingImage::factory()->create(['listing_id' => $listings[$key]->id]);
        }

        $conversations = [];
        $messages = [];

        foreach (['regular', 'agency', 'admin', 'suspended_regular'] as $key) {
            $conversations[$key] = Conversation::factory()->create([
                'listing_id' => $otherListing->id,
                'buyer_id' => $users[$key]->id,
                'owner_id' => $other->id,
            ]);
            $messages[$key] = ChatMessage::factory()->create([
                'conversation_id' => $conversations[$key]->id,
                'sender_id' => $other->id,
            ]);
        }

        $savedSearches = [];

        foreach (['regular', 'agency', 'suspended_regular'] as $key) {
            $savedSearches[$key] = SavedSearch::factory()->create(['user_id' => $users[$key]->id]);
        }

        $changedAgency = Agency::factory()->approved()->create();
        AgencyProfileChange::factory()->create(['agency_id' => $changedAgency->id]);

        $reportedListing = Listing::factory()->approved()->create();
        ListingReport::factory()->create(['listing_id' => $reportedListing->id]);

        $this->fx = [
            'user' => $users,
            'agency' => $agencies,
            'other' => $other,
            'otherListing' => $otherListing,
            'listing' => $listings,
            'sold' => $sold,
            'image' => $images,
            'conversation' => $conversations,
            'message' => $messages,
            'savedSearch' => $savedSearches,
            'pendingListing' => Listing::factory()->pendingReview()->create(),
            'pendingAgency' => Agency::factory()->pendingVerification()->create(),
            'changedAgency' => $changedAgency,
            'victim' => User::factory()->regularUser()->create(),
            'suspendedVictim' => User::factory()->regularUser()->suspended()->create(),
            'reportedListing' => $reportedListing,
            'chatReport' => ReportedChatMessage::factory()->create(),
            'contactMessage' => ContactMessage::factory()->create(),
        ];
    }
}
