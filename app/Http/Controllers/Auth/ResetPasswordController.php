<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    public function show(string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'translations' => __('app.auth.reset_password'),
            'layout' => __('app.auth.layout'),
            'token' => $token,
            'email' => request()->string('email')->value(),
        ]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password = $password;
                $user->setRememberToken(Str::random(60));
                $user->save();

                Event::dispatch(new PasswordReset($user));
            }
        );

        if ($status === Password::PasswordReset) {
            return redirect('/login')->with('success', __('auth.reset_success'));
        }

        return back()->with('error', __('auth.reset_failed'));
    }
}
