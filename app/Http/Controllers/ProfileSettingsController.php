<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\ProfileSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileSettingsController extends Controller
{
    public function __construct(
        private ProfileSettingsService $profileSettings,
    ) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Settings', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'translations' => __('app.profile_settings'),
        ]);
    }

    public function updateAccount(UpdateAccountRequest $request): RedirectResponse
    {
        $this->profileSettings->updateAccount($request->user(), $request->validated());

        return redirect()->route('profile.settings.edit')->with('success', __('app.profile_settings.account.flash'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->profileSettings->updatePassword($request->user(), $request->validated('password'));

        return redirect()->route('profile.settings.edit')->with('success', __('app.profile_settings.password.flash'));
    }
}
