<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaticPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'about' => ['/about', 'about'],
            'privacy policy' => ['/privacy-policy', 'privacy'],
            'terms of service' => ['/terms', 'terms'],
            'cookie policy' => ['/cookie-policy', 'cookies'],
        ];
    }

    #[DataProvider('pages')]
    public function test_guest_can_view_static_page(string $url, string $key): void
    {
        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Static/Show')
                ->where('page.title', __("pages.{$key}.title"))
                ->where('page.lead', __("pages.{$key}.lead"))
                ->has('page.sections'));
    }

    #[DataProvider('pages')]
    public function test_authenticated_user_can_view_static_page(string $url, string $key): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Static/Show')
                ->where('page.title', __("pages.{$key}.title")));
    }

    public function test_legal_pages_show_last_updated_and_about_does_not(): void
    {
        $this->get('/privacy-policy')
            ->assertInertia(fn (Assert $page) => $page->where('page.updated', __('pages.privacy.updated')));

        $this->get('/about')
            ->assertInertia(fn (Assert $page) => $page->where('page.updated', null));
    }

    public function test_every_section_has_a_heading_and_paragraphs(): void
    {
        foreach (['about', 'privacy', 'terms', 'cookies'] as $key) {
            foreach (__("pages.{$key}.sections") as $section) {
                $this->assertNotEmpty($section['heading']);
                $this->assertNotEmpty($section['paragraphs']);
            }
        }
    }
}
