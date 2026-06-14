<?php

namespace App\Services;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AgencyService
{
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
