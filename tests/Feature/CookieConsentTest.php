<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_consent_is_null_when_no_choice_was_made(): void
    {
        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('cookieConsent', null)
                ->where('cookieBanner', __('app.cookie_banner')));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function consentValues(): array
    {
        return [
            'accepted' => ['accepted'],
            'rejected' => ['rejected'],
        ];
    }

    #[DataProvider('consentValues')]
    public function test_stored_client_side_choice_is_shared(string $consent): void
    {
        $this->withUnencryptedCookie('cookie_consent', $consent)
            ->get('/listings')
            ->assertInertia(fn (Assert $page) => $page->where('cookieConsent', $consent));
    }

    public function test_unknown_consent_value_is_treated_as_no_choice(): void
    {
        $this->withUnencryptedCookie('cookie_consent', 'maybe')
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page->where('cookieConsent', null));
    }

    public function test_consent_is_shared_for_signed_in_users_too(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->withUnencryptedCookie('cookie_consent', 'accepted')
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->where('cookieConsent', 'accepted'));
    }
}
