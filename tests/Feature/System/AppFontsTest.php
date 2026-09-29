<?php

namespace Tests\Feature\System;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AppFontsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_app_layout_renders_archivo_font_face(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(
            1,
            preg_match('/@font-face\s*\{[^}]*font-family:\s*["\']Archivo["\']/', $html),
            'The app layout renders no Archivo @font-face.',
        );
    }

    public function test_app_layout_renders_no_legacy_font_faces(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['Inter', 'Playfair Display'] as $family) {
            $this->assertSame(
                0,
                preg_match('/font-family:\s*["\']'.preg_quote($family, '/').'["\']/', $html),
                "The app layout still renders a {$family} font face.",
            );
        }
    }
}
