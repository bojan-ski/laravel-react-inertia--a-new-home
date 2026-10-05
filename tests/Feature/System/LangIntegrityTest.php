<?php

namespace Tests\Feature\System;

use App\Enums\Amenity;
use App\Http\Requests\StoreListingRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use ReflectionClassConstant;
use Tests\TestCase;

class LangIntegrityTest extends TestCase
{
    private const SCANNED = ['app', 'bootstrap', 'routes', 'resources/views'];

    private const LANG_CALL = '/(?<![\w>:$])(?:__|trans_choice|trans|Lang::get|@lang)\(/';

    private const BLOCK_PROP = '/(?:translations|Translations|Labels)$|^(?:layout|landing|countries|page|nav|ui|footer|adminNav|messageNotifications|cookieBanner)$/';

    /**
     * The static prefix of every key built at run time, mapped to the full keys it can produce.
     *
     * @return array<string, list<string>>
     */
    private function runTimeKeys(): array
    {
        $pages = ['about', 'privacy', 'terms', 'cookies'];
        $propertyTypes = (new ReflectionClassConstant(StoreListingRequest::class, 'PROPERTY_TYPES'))->getValue();
        $ranges = collect(['price', 'area'])->crossJoin(['range', 'from', 'up_to'])
            ->map(fn (array $pair): string => "app.saved_searches.summary.{$pair[0]}_{$pair[1]}");

        return [
            'pages.' => collect($pages)->flatMap(fn (string $page): array => [
                "pages.{$page}",
                "pages.{$page}.title",
                "pages.{$page}.lead",
            ])->all(),
            'app.listings.filters.property_types.' => array_map(fn (string $type): string => "app.listings.filters.property_types.{$type}", $propertyTypes),
            'app.listings.amenities.' => array_map(fn (Amenity $amenity): string => "app.listings.amenities.{$amenity->value}", Amenity::cases()),
            'app.saved_searches.summary.' => $ranges->all(),
        ];
    }

    public function test_every_literal_lang_key_resolves(): void
    {
        $missing = collect($this->literalKeys())
            ->reject(fn (array $files, string $key): bool => Lang::has($key))
            ->map(fn (array $files, string $key): string => $key.' ('.implode(', ', array_unique($files)).')')
            ->values()
            ->all();

        $this->assertNotEmpty($this->literalKeys());
        $this->assertSame([], $missing, 'Lang keys read by PHP that do not resolve.');
    }

    public function test_every_key_built_at_run_time_resolves_for_every_value(): void
    {
        $missing = collect($this->runTimeKeys())
            ->flatten()
            ->reject(fn (string $key): bool => Lang::has($key))
            ->values()
            ->all();

        $this->assertSame([], $missing, 'Run-time lang keys that do not resolve.');
        $this->assertIsArray(__('pages.about'));
    }

    public function test_every_key_built_at_run_time_is_listed(): void
    {
        $prefixes = collect($this->langCalls())
            ->flatMap(fn (array $call): array => $call['interpolated'])
            ->unique()
            ->sort()
            ->values()
            ->all();

        $listed = array_keys($this->runTimeKeys());
        sort($listed);

        $this->assertSame($listed, $prefixes, 'A lang key is built at run time without a list in this test.');
    }

    public function test_keys_used_as_blocks_resolve_to_arrays(): void
    {
        $blocks = [];

        foreach ($this->sourceFiles() as $path => $source) {
            preg_match_all("/'(?<prop>\w+)'\s*=>\s*(?:fn\s*\(\)\s*=>\s*)?(?:\\\$\w+\s*\?\s*)?__\('(?<key>[^']+)'\)/", $source, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                if (preg_match(self::BLOCK_PROP, $match['prop'])) {
                    $blocks[$match['key']] = $path;
                }
            }
        }

        $notArrays = collect($blocks)
            ->reject(fn (string $path, string $key): bool => is_array(__($key)))
            ->map(fn (string $path, string $key): string => "{$key} ({$path})")
            ->values()
            ->all();

        $this->assertGreaterThan(30, count($blocks));
        $this->assertSame([], $notArrays, 'Lang keys passed as blocks that are not arrays.');
    }

    /**
     * @return array<string, list<string>>
     */
    private function literalKeys(): array
    {
        $keys = [];

        foreach ($this->langCalls() as $call) {
            foreach ($call['literals'] as $key) {
                $keys[$key][] = $call['path'];
            }
        }

        ksort($keys);

        return $keys;
    }

    /**
     * @return list<array{path: string, literals: list<string>, interpolated: list<string>}>
     */
    private function langCalls(): array
    {
        $prefix = '(?:'.implode('|', $this->langFiles()).')';
        $calls = [];

        foreach ($this->sourceFiles() as $path => $source) {
            preg_match_all(self::LANG_CALL, $source, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$match, $offset]) {
                $arguments = $this->arguments($source, $offset + strlen($match));

                preg_match_all("/'({$prefix}\.[\w.\-]+)'/", $arguments, $literals);
                preg_match_all("/\"({$prefix}\.[\w.\-]*?)\{\\\$/", $arguments, $interpolated);

                $calls[] = [
                    'path' => $path,
                    'literals' => $literals[1],
                    'interpolated' => $interpolated[1],
                ];
            }
        }

        return $calls;
    }

    private function arguments(string $source, int $start): string
    {
        $depth = 1;
        $quote = null;

        for ($i = $start, $length = strlen($source); $i < $length; $i++) {
            $char = $source[$i];

            if ($quote !== null) {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '\'' || $char === '"') {
                $quote = $char;
            } elseif ($char === '(') {
                $depth++;
            } elseif ($char === ')' && --$depth === 0) {
                return substr($source, $start, $i - $start);
            }
        }

        return substr($source, $start);
    }

    /**
     * @return array<string, string>
     */
    private function sourceFiles(): array
    {
        $files = [];

        foreach (self::SCANNED as $directory) {
            foreach (File::allFiles(base_path($directory)) as $file) {
                if ($file->getExtension() === 'php') {
                    $files[str_replace('\\', '/', $file->getRelativePathname())] = $file->getContents();
                }
            }
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function langFiles(): array
    {
        return array_map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME), glob(lang_path('en/*.php')));
    }
}
