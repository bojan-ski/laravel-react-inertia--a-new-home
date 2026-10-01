<?php

namespace Tests\Feature\Seo;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\User;
use App\Services\Seo\SearchIndexing;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SeoRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const HOST = 'https://newbeginning.example';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => self::HOST.'/', 'seo.indexable' => true]);
    }

    public function test_robots_disallows_everything_when_indexing_is_disabled(): void
    {
        config(['seo.indexable' => false]);

        $response = $this->get('/robots.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
    }

    public function test_robots_allows_crawling_and_names_the_sitemap_when_indexing_is_enabled(): void
    {
        $this->assertSame(
            "User-agent: *\nDisallow:\n\nSitemap: ".self::HOST."/sitemap.xml\n",
            $this->get('http://www.other.test/robots.txt')->assertOk()->getContent(),
        );
    }

    public function test_the_static_robots_file_does_not_shadow_the_route(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    public function test_the_sitemap_lists_only_the_public_pages_on_the_canonical_host(): void
    {
        $visible = Agency::factory()->approved()->create();
        $pending = Agency::factory()->pendingVerification()->create();
        $hidden = Agency::factory()->hidden()->create();
        $suspended = Agency::factory()->approved()->for(User::factory()->agency()->suspended(), 'user')->create();
        $listing = Listing::factory()->approved()->create();

        $response = $this->get('http://www.other.test/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));

        $xml = simplexml_load_string((string) $response->getContent());

        $this->assertNotFalse($xml);

        $locations = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));

        $this->assertSame([
            self::HOST.'/',
            self::HOST.'/listings',
            self::HOST.'/agencies',
            self::HOST.'/about',
            self::HOST.'/contact',
            self::HOST.'/privacy-policy',
            self::HOST.'/terms',
            self::HOST.'/cookie-policy',
            self::HOST.'/agencies/'.$visible->slug,
        ], $locations);

        foreach ([$pending, $hidden, $suspended] as $agency) {
            $this->assertNotContains(self::HOST.'/agencies/'.$agency->slug, $locations);
        }

        $this->assertNotContains(self::HOST.'/listings/'.$listing->slug, $locations);
    }

    public function test_sitemap_entries_carry_an_atom_lastmod_for_records(): void
    {
        $agency = Agency::factory()->approved()->create();

        $xml = simplexml_load_string((string) $this->get('/sitemap.xml')->getContent());
        $lastmods = array_map('strval', $xml->xpath('//*[local-name()="lastmod"]'));

        $this->assertSame([$agency->fresh()->updated_at->toAtomString()], $lastmods);
    }

    public function test_every_indexable_route_is_a_page_a_guest_can_open(): void
    {
        $names = [...SearchIndexing::STATIC_ROUTES, ...SearchIndexing::RECORD_ROUTES];

        $this->assertSame([
            'home',
            'listings.index',
            'agencies.index',
            'pages.about',
            'contact.show',
            'pages.privacy',
            'pages.terms',
            'pages.cookies',
            'agencies.show',
        ], $names);

        foreach ($names as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "{$name} is not a registered route");
            $this->assertContains('GET', $route->methods());
            $this->assertNotContains('auth', $route->gatherMiddleware(), "{$name} is behind login");
        }

        $indexing = app(SearchIndexing::class);

        $this->assertFalse($indexing->isIndexable('listings.show'));
        $this->assertFalse($indexing->isIndexable('login'));
        $this->assertFalse($indexing->isIndexable(null));
    }

    public function test_private_pages_are_noindex_when_indexing_is_enabled(): void
    {
        $noindex = '<meta name="robots" content="noindex" data-inertia="robots">';

        foreach (['/login', '/register', '/forgot-password'] as $path) {
            $this->get($path)->assertOk()->assertSee($noindex, false);
        }

        $this->actingAs(User::factory()->regularUser()->create());

        foreach (['/profile', '/bookmarks', '/conversations', '/my-listings/create'] as $path) {
            $this->get($path)->assertOk()->assertSee($noindex, false);
        }

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee($noindex, false);
    }
}
