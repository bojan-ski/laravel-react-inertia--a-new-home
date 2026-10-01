<?php

namespace App\Services\Seo;

class CanonicalUrl
{
    public static function home(): string
    {
        return self::root().'/';
    }

    public static function for(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return self::root().($path === '' ? '/' : $path);
    }

    public static function route(string $name, mixed $parameters = []): string
    {
        return self::for(route($name, $parameters));
    }

    private static function root(): string
    {
        return rtrim((string) config('app.url'), '/');
    }
}
