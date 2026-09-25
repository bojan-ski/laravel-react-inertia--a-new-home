<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FooterTest extends TestCase
{
    public function test_footer_translations_are_shared_without_press_and_careers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('footer.about', __('app.footer.about'))
                ->where('footer.contact', __('app.footer.contact'))
                ->where('footer.gdpr', __('app.footer.gdpr'))
                ->missing('footer.press')
                ->missing('footer.careers'));
    }
}
