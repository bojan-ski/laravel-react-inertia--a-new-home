<?php

namespace Tests\Feature\System;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaginationLabelsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function paginationBlocks(): array
    {
        return [
            'listings' => ['app.listings.pagination'],
            'agencies' => ['app.agencies.pagination'],
            'admin listings' => ['app.admin.listings.pagination'],
            'admin agencies' => ['app.admin.agencies.pagination'],
            'admin users' => ['app.admin.users.pagination'],
            'admin listing reports' => ['app.admin.listing_reports.pagination'],
            'admin reported messages' => ['app.admin.reported_messages.pagination'],
            'admin contact messages' => ['app.admin.contact_messages.pagination'],
        ];
    }

    #[DataProvider('paginationBlocks')]
    public function test_pagination_block_has_every_label(string $path): void
    {
        $labels = __($path);

        $this->assertIsArray($labels);

        foreach (['page_of', 'previous', 'next', 'aria_label'] as $key) {
            $this->assertArrayHasKey($key, $labels, "{$path} is missing {$key}");
            $this->assertIsString($labels[$key]);
            $this->assertNotSame('', $labels[$key]);
        }
    }
}
