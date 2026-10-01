<?php

namespace App\Services\Seo;

class MetaDescription
{
    public const LENGTH = 160;

    public static function excerpt(string $text): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        if (mb_strlen($text) <= self::LENGTH) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::LENGTH);
        $boundary = mb_strrpos($cut, ' ');

        return rtrim($boundary === false ? $cut : mb_substr($cut, 0, $boundary));
    }
}
