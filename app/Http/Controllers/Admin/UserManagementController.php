<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __construct(private UserAccountService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $actor = $request->user();

        $filters = [
            'role' => $request->string('role')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: null,
        ];

        $users = $this->service->getPaginatedUsers($filters)
            ->through(fn (User $user): array => $user->toAdminListArray($actor));

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $filters,
            'translations' => __('app.admin.users'),
        ]);
    }

    public function suspend(User $user): RedirectResponse
    {
        Gate::authorize('suspend', $user);

        $this->service->suspend($user);

        return back()->with('success', __('app.admin.users.flash.suspended'));
    }

    public function reactivate(User $user): RedirectResponse
    {
        Gate::authorize('reactivate', $user);

        $this->service->reactivate($user);

        return back()->with('success', __('app.admin.users.flash.reactivated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $this->service->delete($user);

        return back()->with('success', __('app.admin.users.flash.deleted'));
    }
}
