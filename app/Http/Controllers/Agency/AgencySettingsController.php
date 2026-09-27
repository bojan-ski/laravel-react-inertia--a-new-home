<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\ProfileSettingsService;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AgencySettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Profile/Settings', [
            'translations' => array_replace_recursive(__('app.profile_settings'), __('app.agency_settings')),
            'backHref' => route('agency.index', absolute: false),
            'passwordUrl' => route('agency.settings.password.update', absolute: false),
            'deleteUrl' => route('agency.account.destroy', absolute: false),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request, ProfileSettingsService $profileSettings): RedirectResponse
    {
        $profileSettings->updatePassword($request->user(), $request->validated('password'));

        return redirect()->route('agency.settings.edit')->with('success', __('app.profile_settings.password.flash'));
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
