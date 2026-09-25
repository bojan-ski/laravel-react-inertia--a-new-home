<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public const COOKIE_CONSENT = 'cookie_consent';

    private const CONSENT_VALUES = ['accepted', 'rejected'];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'name' => fn () => __('app.name'),
            'nav' => fn () => __('app.nav'),
            'footer' => fn () => __('app.footer'),
            'adminNav' => fn () => __('app.admin.nav'),
            'cookieConsent' => fn () => $this->cookieConsent($request),
            'cookieBanner' => fn () => __('app.cookie_banner'),
        ];
    }

    private function cookieConsent(Request $request): ?string
    {
        $consent = $request->cookie(self::COOKIE_CONSENT);

        return in_array($consent, self::CONSENT_VALUES, true) ? $consent : null;
    }
}
