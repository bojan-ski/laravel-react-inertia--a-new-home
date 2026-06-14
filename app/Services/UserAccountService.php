<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserAccountService
{
    private const PER_PAGE = 20;

    public function __construct(private MailNotificationService $mail) {}

    public function suspend(User $user): User
    {
        $user->forceFill([
            'status' => UserStatus::Suspended,
            'suspended_at' => now(),
        ])->save();

        $this->mail->sendUserSuspended($user);

        return $user->fresh();
    }

    public function reactivate(User $user): User
    {
        $user->forceFill([
            'status' => UserStatus::Active,
            'suspended_at' => null,
        ])->save();

        return $user->fresh();
    }

    public function delete(User $user): void
    {
        $name = $user->name;
        $email = $user->email;

        $user->delete();

        $this->mail->sendAccountDeleted($name, $email);
    }

    /**
     * @param  array{role?: ?string, status?: ?string, search?: ?string}  $filters
     */
    public function getPaginatedUsers(array $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->whereIn('role', [UserRole::RegularUser, UserRole::Agency]);

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
