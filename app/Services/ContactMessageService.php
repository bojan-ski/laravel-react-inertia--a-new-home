<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContactMessageService
{
    private const PER_PAGE = 20;

    /**
     * @param  array{email: string, subject: string, message: string}  $data
     */
    public function create(array $data, ?User $sender): ContactMessage
    {
        $contactMessage = new ContactMessage($data);
        $contactMessage->user_id = $sender?->id;
        $contactMessage->save();

        return $contactMessage;
    }

    public function paginateForAdmin(bool $handled): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->when($handled, fn ($query) => $query->whereNotNull('handled_at'))
            ->when(! $handled, fn ($query) => $query->whereNull('handled_at'))
            ->with(['user:id,name', 'handledBy:id,name'])
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (ContactMessage $contactMessage): array => $contactMessage->toAdminArray());
    }

    public function markHandled(ContactMessage $contactMessage, User $admin): void
    {
        $contactMessage->handled_at = now();
        $contactMessage->handled_by_user_id = $admin->id;
        $contactMessage->save();
    }

    public function delete(ContactMessage $contactMessage): void
    {
        $contactMessage->delete();
    }
}
