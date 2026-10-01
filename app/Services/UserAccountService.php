<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\Concerns\AppliesLikeSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserAccountService
{
    use AppliesLikeSearch;

    private const PER_PAGE = 20;

    public function __construct(
        private MailNotificationService $mail,
        private ListingImageService $images,
    ) {}

    public function suspend(User $user): User
    {
        if ($user->isSuspended()) {
            return $user;
        }

        $user->forceFill([
            'status' => UserStatus::Suspended,
            'suspended_at' => now(),
        ])->save();

        $this->mail->sendUserSuspended($user);

        return $user->refresh();
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

        $this->deleteImageFilesForOwnedListings($user);

        $user->delete();

        Conversation::whereNull('buyer_id')->whereNull('owner_id')->delete();

        $this->mail->sendAccountDeleted($name, $email);
    }

    /**
     * Remove stored image files for every listing the user owns directly or
     * through their agency, before the hard delete cascades the rows away at
     * the database level (which would otherwise orphan the files on disk).
     */
    private function deleteImageFilesForOwnedListings(User $user): void
    {
        $listingIds = Listing::withTrashed()->where('user_id', $user->id)->pluck('id');

        if ($user->agency !== null) {
            $listingIds = $listingIds->merge(
                Listing::withTrashed()->where('agency_id', $user->agency->id)->pluck('id')
            );
        }

        if ($listingIds->isEmpty()) {
            return;
        }

        ListingImage::whereIn('listing_id', $listingIds)
            ->get()
            ->each(fn (ListingImage $image) => $this->images->delete($image));
    }

    /**
     * @param  array{role?: ?string, status?: ?string, search?: ?string}  $filters
     */
    public function getPaginatedUsers(array $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->with('agency:id,user_id,name,status')
            ->whereIn('role', [UserRole::RegularUser, UserRole::Agency]);

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $this->applyLikeSearch($query, $filters['search'], ['name', 'email']);
        }

        return $query->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
