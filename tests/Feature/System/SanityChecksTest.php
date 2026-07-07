<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class SanityChecksTest extends TestCase
{
    /**
     * @return array<int, string>
     */
    private function appPhpFiles(): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path(), RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    public function test_every_named_route_resolves_to_an_existing_controller_method(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if ($action === 'Closure' || ! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);

            $this->assertTrue(
                method_exists($controller, $method),
                "Route [{$route->uri()}] points at {$controller}@{$method}, which does not exist."
            );
        }
    }

    public function test_no_mail_dispatch_outside_the_notification_service(): void
    {
        $offenders = [];

        foreach ($this->appPhpFiles() as $file) {
            $contents = file_get_contents($file);

            if (str_contains($contents, 'Mail::to(') && ! str_contains($file, 'MailNotificationService')) {
                $offenders[] = $file;
            }
        }

        $this->assertSame([], $offenders, 'Mail::to() must only be called from MailNotificationService.');
    }

    public function test_no_broken_console_attribute_imports(): void
    {
        $offenders = [];

        foreach ($this->appPhpFiles() as $file) {
            if (str_contains(file_get_contents($file), 'Illuminate\\Console\\Attributes\\')) {
                $offenders[] = $file;
            }
        }

        $this->assertSame([], $offenders, 'No file should import Illuminate\\Console\\Attributes\\.');
    }
}
