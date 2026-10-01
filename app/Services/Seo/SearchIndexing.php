<?php

namespace App\Services\Seo;

class SearchIndexing
{
    /**
     * @var list<string>
     */
    public const STATIC_ROUTES = [
        'home',
        'listings.index',
        'agencies.index',
        'pages.about',
        'contact.show',
        'pages.privacy',
        'pages.terms',
        'pages.cookies',
    ];

    /**
     * @var list<string>
     */
    public const RECORD_ROUTES = [
        'agencies.show',
    ];

    public function isEnabled(): bool
    {
        return (bool) config('seo.indexable');
    }

    public function isIndexable(?string $routeName): bool
    {
        return $routeName !== null
            && in_array($routeName, [...self::STATIC_ROUTES, ...self::RECORD_ROUTES], true);
    }

    public function robots(?string $routeName): ?string
    {
        if (! $this->isEnabled()) {
            return 'noindex, nofollow';
        }

        return $this->isIndexable($routeName) ? null : 'noindex';
    }
}
