<?php

use App\Enums\ListingStatus;
use App\Enums\UserStatus;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Revert listings left in `hidden` by the deleted Approach B suspension
     * cascade back to `pending_review` so admins re-review them. A hidden
     * listing is treated as orphaned when neither its owner nor its owning
     * agency's owner is currently suspended. Idempotent and safe to re-run.
     */
    public function up(): void
    {
        $orphanedIds = Listing::query()
            ->where('status', ListingStatus::Hidden->value)
            ->whereDoesntHave('owner', function (Builder $query) {
                $query->where('status', UserStatus::Suspended->value);
            })
            ->whereDoesntHave('agency.user', function (Builder $query) {
                $query->where('status', UserStatus::Suspended->value);
            })
            ->pluck('id');

        if ($orphanedIds->isEmpty()) {
            return;
        }

        Listing::query()
            ->whereIn('id', $orphanedIds)
            ->update([
                'status' => ListingStatus::PendingReview->value,
                'approved_at' => null,
                'rejection_reason' => null,
            ]);

        Log::info('Reverted orphaned-hidden listings to pending_review.', [
            'listing_ids' => $orphanedIds->all(),
        ]);
    }

    /**
     * No-op: the pre-migration orphaned-hidden state is not recoverable and
     * should not be recreated on rollback.
     */
    public function down(): void
    {
        //
    }
};
