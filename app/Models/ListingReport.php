<?php

namespace App\Models;

use App\Enums\ListingReportReason;
use App\Enums\ListingReportResolution;
use Database\Factories\ListingReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reason', 'details'])]
class ListingReport extends Model
{
    /** @use HasFactory<ListingReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => ListingReportReason::class,
            'resolution' => ListingReportResolution::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class)->withTrashed();
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toModerationArray(): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason->value,
            'details' => $this->details,
            'reportedByName' => $this->relationLoaded('reportedBy') ? $this->reportedBy?->name : null,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
