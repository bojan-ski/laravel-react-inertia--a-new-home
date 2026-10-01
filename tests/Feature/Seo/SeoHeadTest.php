<?php

namespace Tests\Feature\Seo;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\Seo\MetaDescription;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SeoHeadTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const HOST = 'https://newbeginning.example';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.name' => 'Acme Homes', 'app.url' => self::HOST.'/', 'seo.indexable' => true]);
    }

    public function test_the_landing_page_renders_the_full_head_in_its_initial_html(): void
    {
        $response = $this->get('/')->assertOk();
        $title = 'Homes for Sale and Rent in Europe — Acme Homes';
        $description = 'Find homes for sale and rent across Europe. Every listing on New Beginning is reviewed before it goes live, from verified owners and agencies.';

        foreach ([
            '<meta name="theme-color" content="#22523a">',
            '<title data-inertia="title">'.$title.'</title>',
            '<meta name="description" content="'.$description.'" data-inertia="description">',
            '<link rel="canonical" href="'.self::HOST.'/" data-inertia="canonical">',
            '<meta property="og:site_name" content="New Beginning" data-inertia="og:site_name">',
            '<meta property="og:type" content="website" data-inertia="og:type">',
            '<meta property="og:locale" content="en_GB" data-inertia="og:locale">',
            '<meta property="og:url" content="'.self::HOST.'/" data-inertia="og:url">',
            '<meta property="og:title" content="'.$title.'" data-inertia="og:title">',
            '<meta property="og:description" content="'.$description.'" data-inertia="og:description">',
            '<meta property="og:image" content="'.self::HOST.'/og-image.jpg" data-inertia="og:image">',
            '<meta property="og:image:width" content="1200" data-inertia="og:image:width">',
            '<meta property="og:image:height" content="630" data-inertia="og:image:height">',
            '<meta name="twitter:card" content="summary_large_image" data-inertia="twitter:card">',
            '<meta name="twitter:title" content="'.$title.'" data-inertia="twitter:title">',
            '<meta name="twitter:image" content="'.self::HOST.'/og-image.jpg" data-inertia="twitter:image">',
            '<link rel="preload" as="image" href="'.asset('images/hero-modern-house-white.webp').'" fetchpriority="high" data-inertia="preload-image">',
        ] as $tag) {
            $response->assertSee($tag, false);
        }

        $documentHead = Str::before((string) $response->getContent(), '</head>');

        $response->assertDontSee('<meta name="robots"', false);
        $this->assertSame(1, substr_count($documentHead, '<title'));
        $this->assertSame(1, substr_count($documentHead, 'name="description"'));
        $this->assertSame(1, substr_count($documentHead, 'rel="canonical"'));
        $this->assertFileExists(public_path('images/hero-modern-house-white.webp'));

        $graph = $this->structuredData($response);

        $this->assertSame(['Organization', 'WebSite'], array_column($graph, '@type'));
        $this->assertSame('New Beginning', $graph[0]['name']);
        $this->assertSame(self::HOST.'/', $graph[0]['url']);
        $this->assertSame(self::HOST.'/icon-512.png', $graph[0]['logo']);
        $this->assertSame(self::HOST.'/#organization', $graph[1]['publisher']['@id']);
    }

    public function test_the_head_page_prop_carries_the_same_elements_the_html_renders(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->where('head', function ($head) use ($response): bool {
                foreach ($head as $element) {
                    $this->assertStringContainsString($element, (string) $response->getContent());
                }

                return count($head) === 20;
            })
        );
    }

    public function test_the_listings_index_canonical_drops_filters_and_pagination(): void
    {
        $response = $this->get('/listings?listingType=rent&page=2&utm_source=mail')->assertOk();

        $response->assertSee('<title data-inertia="title">Listings — Acme Homes</title>', false);
        $response->assertSee('<meta name="description" content="Browse houses, flats, villas and land for sale and rent across Europe. Filter by city, price, size and amenities." data-inertia="description">', false);
        $response->assertSee('<link rel="canonical" href="'.self::HOST.'/listings" data-inertia="canonical">', false);
        $response->assertSee('<meta property="og:url" content="'.self::HOST.'/listings" data-inertia="og:url">', false);
        $response->assertDontSee('<meta name="robots"', false);
        $response->assertDontSee('rel="preload" as="image"', false);

        $crumbs = $this->breadcrumb($response);

        $this->assertSame(['Home', 'Listings'], array_column($crumbs, 'name'));
        $this->assertSame([self::HOST.'/', self::HOST.'/listings'], array_column($crumbs, 'item'));
    }

    public function test_the_agencies_index_has_its_own_description_and_breadcrumb(): void
    {
        $response = $this->get('/agencies?search=harbor')->assertOk();

        $response->assertSee('<title data-inertia="title">Agencies — Acme Homes</title>', false);
        $response->assertSee('<meta name="description" content="Browse verified estate agencies on New Beginning and see the homes each one has for sale and rent." data-inertia="description">', false);
        $response->assertSee('<link rel="canonical" href="'.self::HOST.'/agencies" data-inertia="canonical">', false);

        $this->assertSame(['Home', 'Agencies'], array_column($this->breadcrumb($response), 'name'));
    }

    public function test_an_agency_page_describes_the_agency(): void
    {
        $agency = Agency::factory()->approved()->create([
            'name' => 'Harbor Homes',
            'description' => str_repeat('Calm homes by the water. ', 12),
            'phone' => '+381 11 555 0100',
            'email' => 'office@harbor.example',
            'website' => 'https://harbor.example',
            'address' => 'Quay 4, Kotor',
        ]);
        $url = self::HOST.'/agencies/'.$agency->slug;

        $response = $this->get("/agencies/{$agency->slug}?page=2")->assertOk();
        $description = MetaDescription::excerpt($agency->description);

        $this->assertLessThanOrEqual(160, mb_strlen($description));
        $this->assertStringStartsWith('Calm homes by the water. Calm homes', $description);

        $response->assertSee('<title data-inertia="title">Harbor Homes — Acme Homes</title>', false);
        $response->assertSee('<meta name="description" content="'.$description.'" data-inertia="description">', false);
        $response->assertSee('<link rel="canonical" href="'.$url.'" data-inertia="canonical">', false);
        $response->assertDontSee('<meta name="robots"', false);

        $crumbs = $this->breadcrumb($response);

        $this->assertSame([1, 2, 3], array_column($crumbs, 'position'));
        $this->assertSame(['Home', 'Agencies', 'Harbor Homes'], array_column($crumbs, 'name'));
        $this->assertSame($url, $crumbs[2]['item']);

        $agent = collect($this->structuredData($response))->firstWhere('@type', 'RealEstateAgent');

        $this->assertSame([
            '@type' => 'RealEstateAgent',
            '@id' => $url.'#agency',
            'name' => 'Harbor Homes',
            'url' => $url,
            'description' => $description,
            'telephone' => '+381 11 555 0100',
            'email' => 'office@harbor.example',
            'address' => 'Quay 4, Kotor',
            'sameAs' => 'https://harbor.example',
        ], $agent);
    }

    public function test_an_agency_without_a_description_falls_back_and_omits_empty_fields(): void
    {
        $agency = Agency::factory()->approved()->create([
            'name' => 'Harbor Homes',
            'description' => null,
            'website' => null,
            'address' => null,
        ]);

        $response = $this->get("/agencies/{$agency->slug}")->assertOk();

        $response->assertSee('<meta name="description" content="Harbor Homes is a verified estate agency on New Beginning. Browse its homes for sale and rent." data-inertia="description">', false);

        $agent = collect($this->structuredData($response))->firstWhere('@type', 'RealEstateAgent');

        $this->assertArrayNotHasKey('sameAs', $agent);
        $this->assertArrayNotHasKey('address', $agent);
    }

    public function test_page_text_is_escaped_in_tags_and_structured_data(): void
    {
        $agency = Agency::factory()->approved()->create([
            'name' => 'Aura "</script><b>" & Co',
            'description' => 'Stone <i>&</i> "light"',
        ]);

        $response = $this->get("/agencies/{$agency->slug}")->assertOk();

        $response->assertSee('<meta property="og:title" content="Aura &quot;&lt;/script&gt;&lt;b&gt;&quot; &amp; Co — Acme Homes" data-inertia="og:title">', false);
        $response->assertSee('<meta name="description" content="Stone &lt;i&gt;&amp;&lt;/i&gt; &quot;light&quot;" data-inertia="description">', false);
        $response->assertDontSee('</script><b>', false);

        $this->assertSame('Aura "</script><b>" & Co', $this->breadcrumb($response)[2]['name']);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function publicPages(): array
    {
        return [
            'about' => ['/about', 'About Us', 'New Beginning is a moderated property marketplace built to make finding a home feel calm, clear, and trustworthy.'],
            'privacy policy' => ['/privacy-policy', 'Privacy Policy', 'This policy explains what personal data we collect, why we collect it, and the choices you have.'],
            'terms' => ['/terms', 'Terms of Service', 'These terms govern your use of New Beginning. By creating an account you agree to them.'],
            'cookie policy' => ['/cookie-policy', 'Cookie Policy', 'This policy explains what cookies are, which ones we use, and how you can control them.'],
            'contact' => ['/contact', 'Contact Us', 'Questions about a listing, your account, or partnering with us? Send us a message and our team will get back to you.'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_a_public_page_is_indexable_with_its_own_title_and_description(string $path, string $title, string $description): void
    {
        $response = $this->get($path)->assertOk();

        $response->assertSee('<title data-inertia="title">'.$title.' — Acme Homes</title>', false);
        $response->assertSee('<meta name="description" content="'.e($description).'" data-inertia="description">', false);
        $response->assertSee('<link rel="canonical" href="'.self::HOST.$path.'" data-inertia="canonical">', false);
        $response->assertDontSee('<meta name="robots"', false);

        $this->assertLessThanOrEqual(160, mb_strlen($description));
        $this->assertSame(['Home', $title], array_column($this->breadcrumb($response), 'name'));
    }

    public function test_a_noindex_page_keeps_a_self_referencing_canonical_without_structured_data(): void
    {
        $response = $this->get('/login?redirect=somewhere')->assertOk();

        $response->assertSee('<title data-inertia="title">Acme Homes</title>', false);
        $response->assertSee('<meta name="robots" content="noindex" data-inertia="robots">', false);
        $response->assertSee('<link rel="canonical" href="'.self::HOST.'/login" data-inertia="canonical">', false);
        $response->assertSee('<meta name="description" content="New Beginning is a moderated property marketplace for homes for sale and rent across Europe, listed by verified owners and agencies." data-inertia="description">', false);
        $response->assertDontSee('application/ld+json', false);
    }

    public function test_a_listing_detail_page_is_noindex_and_preloads_its_first_photo(): void
    {
        $listing = Listing::factory()->approved()->create(['title' => 'Stone House in Perast']);
        $first = ListingImage::factory()->for($listing)->create(['sort_order' => 1]);
        ListingImage::factory()->for($listing)->create(['sort_order' => 2]);

        $response = $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk();

        $response->assertSee('<title data-inertia="title">Stone House in Perast — Acme Homes</title>', false);
        $response->assertSee('<meta name="robots" content="noindex" data-inertia="robots">', false);
        $response->assertSee('<link rel="preload" as="image" href="'.e(Storage::disk('public')->url($first->large_path)).'" fetchpriority="high" data-inertia="preload-image">', false);
        $response->assertDontSee('application/ld+json', false);
    }

    public function test_a_listing_without_photos_has_no_preload(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertDontSee('rel="preload" as="image"', false);
    }

    public function test_every_page_is_noindex_nofollow_when_indexing_is_disabled(): void
    {
        config(['seo.indexable' => false]);

        foreach (['/', '/listings', '/about', '/login'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<meta name="robots" content="noindex, nofollow" data-inertia="robots">', false);
        }
    }

    public function test_the_root_template_links_the_icons_and_manifest(): void
    {
        $response = $this->get('/')->assertOk();

        foreach ([
            '<link rel="icon" href="'.asset('favicon.svg').'" type="image/svg+xml">',
            '<link rel="icon" href="'.asset('favicon-32.png').'" sizes="32x32" type="image/png">',
            '<link rel="icon" href="'.asset('favicon-16.png').'" sizes="16x16" type="image/png">',
            '<link rel="apple-touch-icon" href="'.asset('apple-touch-icon.png').'">',
            '<link rel="manifest" href="'.asset('site.webmanifest').'">',
        ] as $tag) {
            $response->assertSee($tag, false);
        }
    }

    public function test_the_icons_manifest_and_share_image_exist_at_their_declared_sizes(): void
    {
        $this->assertSame([1200, 630], array_slice(getimagesize(public_path('og-image.jpg')), 0, 2));
        $this->assertSame([16, 16], array_slice(getimagesize(public_path('favicon-16.png')), 0, 2));
        $this->assertSame([32, 32], array_slice(getimagesize(public_path('favicon-32.png')), 0, 2));
        $this->assertSame([180, 180], array_slice(getimagesize(public_path('apple-touch-icon.png')), 0, 2));
        $this->assertFileExists(public_path('favicon.svg'));
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));

        $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('New Beginning', $manifest['name']);
        $this->assertSame('#22523a', $manifest['theme_color']);

        foreach ($manifest['icons'] as $icon) {
            [$width, $height] = getimagesize(public_path(ltrim($icon['src'], '/')));

            $this->assertSame($icon['sizes'], "{$width}x{$height}");
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function structuredData(TestResponse $response): array
    {
        $matched = preg_match('#<script type="application/ld\+json" data-inertia="structured-data">(.*?)</script>#s', (string) $response->getContent(), $match);

        $this->assertSame(1, $matched, 'No structured data script in the HTML.');

        return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR)['@graph'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function breadcrumb(TestResponse $response): array
    {
        $list = collect($this->structuredData($response))->firstWhere('@type', 'BreadcrumbList');

        $this->assertNotNull($list, 'No BreadcrumbList in the structured data.');

        return $list['itemListElement'];
    }
}
