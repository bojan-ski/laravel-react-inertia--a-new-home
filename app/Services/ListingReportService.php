<?php

namespace App\Services;

use App\Enums\ListingReportResolution;
use App\Exceptions\InvalidListingReportException;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Models\Listing;
use App\Models\ListingReport;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ListingReportService
{
    public function __construct(
        private ListingService $listings,
        private MailNotificationService $mail,
    ) {}

    /**
     * @param  array{reason: string, details?: string|null}  $data
     *
     * @throws InvalidListingReportException
     */
    public function report(Listing $listing, User $reporter, array $data): ListingReport
    {
        $alreadyReported = $listing->reports()
            ->where('reported_by_user_id', $reporter->id)
            ->exists();

        if ($alreadyReported) {
            throw new InvalidListingReportException(__('app.listing_reports.errors.duplicate'));
        }

        $report = new ListingReport([
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
        ]);

        $report->listing_id = $listing->id;
        $report->reported_by_user_id = $reporter->id;

        try {
            $report->save();
        } catch (UniqueConstraintViolationException) {
            throw new InvalidListingReportException(__('app.listing_reports.errors.duplicate'));
        }

        return $report;
    }

    public function dismiss(Listing $listing, User $admin): int
    {
        return $this->resolveOpenReports($listing, $admin, ListingReportResolution::Dismissed);
    }

    /**
     * @throws InvalidListingStatusTransitionException
     */
    public function takeDown(Listing $listing, User $admin, string $reason): Listing
    {
        $listing = DB::transaction(function () use ($listing, $admin, $reason): Listing {
            $locked = Listing::query()->lockForUpdate()->findOrFail($listing->id);

            $this->listings->takeDown($locked, $reason);
            $this->resolveOpenReports($locked, $admin, ListingReportResolution::TakenDown);

            return $locked;
        });

        $this->mail->sendListingTakenDown($listing);

        return $listing;
    }

    private function resolveOpenReports(Listing $listing, User $admin, ListingReportResolution $resolution): int
    {
        return $listing->openReports()->update([
            'resolved_at' => now(),
            'resolved_by_user_id' => $admin->id,
            'resolution' => $resolution->value,
        ]);
    }
}
