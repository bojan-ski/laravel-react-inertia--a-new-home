<?php

namespace App\Models;

use Database\Factories\AgencyProfileChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['proposed_values'])]
class AgencyProfileChange extends Model
{
    /** @use HasFactory<AgencyProfileChangeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'proposed_values' => 'encrypted:array',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toOwnerArray(): array
    {
        return [
            'submittedAt' => $this->updated_at->toIso8601String(),
            'changes' => $this->proposed_values,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toModerationArray(): array
    {
        return [
            'agencyId' => $this->agency_id,
            'agencyName' => $this->agency?->name,
            'agencySlug' => $this->agency?->slug,
            'ownerName' => $this->agency?->user?->name,
            'ownerEmail' => $this->agency?->user?->email,
            'submittedAt' => $this->updated_at->toIso8601String(),
            'changes' => collect($this->proposed_values)
                ->map(fn (?string $proposed, string $field): array => [
                    'field' => $field,
                    'current' => $this->agency?->profileValue($field),
                    'proposed' => $proposed,
                ])
                ->values()
                ->all(),
        ];
    }
}
