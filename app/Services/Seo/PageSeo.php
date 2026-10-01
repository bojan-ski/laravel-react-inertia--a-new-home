<?php

namespace App\Services\Seo;

final readonly class PageSeo
{
    /**
     * @param  list<array{name: string, url: string}>  $breadcrumbs
     * @param  list<array<string, mixed>>  $structuredData
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $breadcrumbs = [],
        public ?string $preloadImageUrl = null,
        public array $structuredData = [],
    ) {}

    public static function defaults(): self
    {
        return new self('', __('app.seo.description'));
    }

    public static function forPage(string $title, string $description, string $url, ?string $preloadImageUrl = null): self
    {
        return new self(
            title: $title,
            description: MetaDescription::excerpt($description),
            breadcrumbs: [
                ['name' => __('app.seo.breadcrumb_home'), 'url' => route('home')],
                ['name' => $title, 'url' => $url],
            ],
            preloadImageUrl: $preloadImageUrl,
        );
    }

    public function documentTitle(): string
    {
        $name = (string) __('app.name');

        return $this->title === '' ? $name : "{$this->title} — {$name}";
    }
}
