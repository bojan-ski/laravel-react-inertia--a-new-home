<?php

namespace App\Services\Seo;

use Illuminate\Http\Request;

class SeoHead
{
    public function __construct(private SearchIndexing $indexing) {}

    /**
     * @return list<string>
     */
    public function render(PageSeo $seo, Request $request): array
    {
        $routeName = $request->route()?->getName();
        $url = CanonicalUrl::for($request->url());
        $title = $seo->documentTitle();
        $robots = $this->indexing->robots($routeName);
        $image = CanonicalUrl::for(asset(config('seo.image.path')));
        $imageAlt = __('app.seo.image_alt');

        return array_values(array_filter([
            $this->tag('title', 'title', [], $title),
            $this->meta('description', ['name' => 'description', 'content' => $seo->description]),
            $robots === null ? null : $this->meta('robots', ['name' => 'robots', 'content' => $robots]),
            $this->tag('link', 'canonical', ['rel' => 'canonical', 'href' => $url]),
            $this->meta('og:site_name', ['property' => 'og:site_name', 'content' => __('app.seo.site_name')]),
            $this->meta('og:type', ['property' => 'og:type', 'content' => 'website']),
            $this->meta('og:locale', ['property' => 'og:locale', 'content' => config('seo.locale')]),
            $this->meta('og:url', ['property' => 'og:url', 'content' => $url]),
            $this->meta('og:title', ['property' => 'og:title', 'content' => $title]),
            $this->meta('og:description', ['property' => 'og:description', 'content' => $seo->description]),
            $this->meta('og:image', ['property' => 'og:image', 'content' => $image]),
            $this->meta('og:image:width', ['property' => 'og:image:width', 'content' => (string) config('seo.image.width')]),
            $this->meta('og:image:height', ['property' => 'og:image:height', 'content' => (string) config('seo.image.height')]),
            $this->meta('og:image:alt', ['property' => 'og:image:alt', 'content' => $imageAlt]),
            $this->meta('twitter:card', ['name' => 'twitter:card', 'content' => 'summary_large_image']),
            $this->meta('twitter:title', ['name' => 'twitter:title', 'content' => $title]),
            $this->meta('twitter:description', ['name' => 'twitter:description', 'content' => $seo->description]),
            $this->meta('twitter:image', ['name' => 'twitter:image', 'content' => $image]),
            $this->meta('twitter:image:alt', ['name' => 'twitter:image:alt', 'content' => $imageAlt]),
            $seo->preloadImageUrl === null
                ? null
                : $this->tag('link', 'preload-image', ['rel' => 'preload', 'as' => 'image', 'href' => $seo->preloadImageUrl, 'fetchpriority' => 'high']),
            $this->indexing->isIndexable($routeName) ? $this->structuredData($seo) : null,
        ]));
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function meta(string $key, array $attributes): string
    {
        return $this->tag('meta', $key, $attributes);
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function tag(string $element, string $key, array $attributes, ?string $content = null): string
    {
        $rendered = collect([...$attributes, 'data-inertia' => $key])
            ->map(fn (string $value, string $name): string => $name.'="'.e($value).'"')
            ->implode(' ');

        return $content === null
            ? "<{$element} {$rendered}>"
            : "<{$element} {$rendered}>".e($content)."</{$element}>";
    }

    private function structuredData(PageSeo $seo): string
    {
        $home = CanonicalUrl::home();
        $siteName = __('app.seo.site_name');

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $home.'#organization',
                'name' => $siteName,
                'url' => $home,
                'logo' => CanonicalUrl::for(asset(config('seo.logo'))),
            ],
            [
                '@type' => 'WebSite',
                '@id' => $home.'#website',
                'name' => $siteName,
                'url' => $home,
                'inLanguage' => app()->getLocale(),
                'publisher' => ['@id' => $home.'#organization'],
            ],
        ];

        if ($seo->breadcrumbs !== []) {
            $graph[] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_map(fn (array $crumb, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['name'],
                    'item' => CanonicalUrl::for($crumb['url']),
                ], $seo->breadcrumbs, array_keys($seo->breadcrumbs)),
            ];
        }

        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => [...$graph, ...$seo->structuredData]],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR,
        );

        return '<script type="application/ld+json" data-inertia="structured-data">'.$json.'</script>';
    }
}
