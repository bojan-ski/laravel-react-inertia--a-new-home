<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\ProfileSettingsService;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileSettingsController extends Controller
{
    public function __construct(
        private ProfileSettingsService $profileSettings,
    ) {}

    public function edit(): Response
    {
        return Inertia::render('Profile/Settings', [
            'translations' => __('app.profile_settings'),
            'backHref' => route('profile.index', absolute: false),
            'passwordUrl' => route('profile.settings.password.update', absolute: false),
            'deleteUrl' => route('profile.destroy', absolute: false),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->profileSettings->updatePassword($request->user(), $request->validated('password'));

        return redirect()->route('profile.settings.edit')->with('success', __('app.profile_settings.password.flash'));
    }

    public function destroy(DeleteAccountRequest $request, UserAccountService $accounts): RedirectResponse
    {
        $user = $request->user();

        Auth::guard('web')->logout();

        $accounts->delete($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', __('app.profile_settings.delete.flash'));
    }
}
