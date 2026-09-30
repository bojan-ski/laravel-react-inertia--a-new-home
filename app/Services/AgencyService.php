<?php

namespace App\Services;

use App\Enums\AgencyStatus;
use App\Exceptions\InvalidAgencyStatusTransitionException;
use App\Http\Requests\AgencyIndexRequest;
use App\Models\Agency;
use App\Models\User;
use App\Services\Concerns\AppliesLikeSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AgencyService
{
    use AppliesLikeSearch;

    private const PER_PAGE = 12;

    public function __construct(private MailNotificationService $mail) {}

    public function getPublicAgencies(AgencyIndexRequest $request): LengthAwarePaginator
    {
        $search = $request->filters()['search'];

        $query = Agency::actuallyPubliclyVisible()
            ->withCount(['listings as public_listings_count' => fn (Builder $query) => $query->actuallyPubliclyVisible()]);

        if ($search !== '') {
            $this->applyLikeSearch($query, $search, ['name', 'description', 'address']);
        }

        return $query->orderByDesc('approved_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws \RuntimeException
     */
    public function createForUser(User $user, array $data): Agency
    {
        if ($user->agency !== null) {
            throw new \RuntimeException('User already has an agency record.');
        }

        $agency = new Agency(Arr::only($data, (new Agency)->getFillable()));

        $agency->user_id = $user->id;
        $agency->slug = $this->generateUniqueSlug($data['name']);
        $agency->status = AgencyStatus::PendingVerification;
        $agency->approved_at = null;
        $agency->rejection_reason = null;
        $agency->logo_path = null;

        $agency->save();

        return $agency;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Agency $agency, array $data): Agency
    {
        $agency->fill(Arr::only($data, Agency::PROFILE_FIELDS));

        $agency->save();

        return $agency->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitChanges(Agency $agency, array $data): bool
    {
        $changes = collect(Agency::PROFILE_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => $this->normalize($data[$field] ?? null)])
            ->reject(fn (?string $proposed, string $field): bool => $proposed === $agency->profileValue($field))
            ->all();

        if ($changes === []) {
            $agency->pendingChange()->delete();

            return false;
        }

        $agency->pendingChange()->updateOrCreate([], ['proposed_values' => $changes]);

        return true;
    }

    /**
     * @throws InvalidAgencyStatusTransitionException
     */
    public function approveChanges(Agency $agency): Agency
    {
        $change = $agency->pendingChange;

        if ($change === null) {
            throw new InvalidAgencyStatusTransitionException('This agency has no pending profile changes.');
        }

        $this->update($agency, $change->proposed_values);
        $change->delete();

        $this->mail->sendAgencyChangesApproved($agency);

        return $agency->refresh();
    }

    /**
     * @throws InvalidAgencyStatusTransitionException
     */
    public function rejectChanges(Agency $agency, string $reason): void
    {
        $change = $agency->pendingChange;

        if ($change === null) {
            throw new InvalidAgencyStatusTransitionException('This agency has no pending profile changes.');
        }

        $change->delete();

        $this->mail->sendAgencyChangesRejected($agency, $reason);
    }

    private function normalize(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * @throws InvalidAgencyStatusTransitionException
     */
    public function approve(Agency $agency): Agency
    {
        if (! $agency->canBeModerated()) {
            throw new InvalidAgencyStatusTransitionException('This agency cannot be approved.');
        }

        $agency->status = AgencyStatus::Approved;
        $agency->approved_at = now();
        $agency->rejection_reason = null;
        $agency->save();

        $this->mail->sendAgencyApproved($agency);

        return $agency->refresh();
    }

    /**
     * @throws InvalidAgencyStatusTransitionException
     */
    public function reject(Agency $agency, string $reason): Agency
    {
        if (! $agency->canBeModerated()) {
            throw new InvalidAgencyStatusTransitionException('This agency cannot be rejected.');
        }

        $agency->status = AgencyStatus::Rejected;
        $agency->rejection_reason = $reason;
        $agency->approved_at = null;
        $agency->save();

        $this->mail->sendAgencyRejected($agency);

        $agency->delete();

        return $agency;
    }

    private function generateUniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $suffix = 2;

        while (Agency::where('slug', $candidate)->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
