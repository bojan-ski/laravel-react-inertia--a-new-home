<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthPageTranslationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function guestAuthPages(): array
    {
        return [
            'login' => ['/login', 'Auth/Login', 'app.auth.login'],
            'register' => ['/register', 'Auth/Register', 'app.auth.register'],
            'forgot password' => ['/forgot-password', 'Auth/ForgotPassword', 'app.auth.forgot_password'],
        ];
    }

    #[DataProvider('guestAuthPages')]
    public function test_auth_page_receives_its_translations_and_the_layout_copy(string $url, string $component, string $key): void
    {
        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->where('translations', __($key))
                ->where('layout', __('app.auth.layout')));
    }

    public function test_reset_password_page_receives_its_translations_and_the_layout_copy(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get("/reset-password/{$token}?email={$user->email}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ResetPassword')
                ->where('translations', __('app.auth.reset_password'))
                ->where('layout', __('app.auth.layout')));
    }
}
