<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Auth/Register', [
            'translations' => __('app.auth.register'),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $role = UserRole::from($request->string('account_type')->value());

        abort_if($role === UserRole::Admin, 403);

        $user = new User([
            'name' => $request->string('name')->value(),
            'email' => $request->string('email')->value(),
            'password' => $request->string('password')->value(),
        ]);
        $user->role = $role;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->redirectPathForRole($role));
    }

    protected function redirectPathForRole(UserRole $role): string
    {
        return match ($role) {
            UserRole::Agency => '/agency/setup',
            default => '/profile',
        };
    }
}
