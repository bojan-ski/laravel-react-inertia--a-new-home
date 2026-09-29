import { Link } from '@inertiajs/react'
import ListingReportActions from '@/Components/Admin/ListingReportActions'
import ListingReportList from '@/Components/Admin/ListingReportList'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import type { ListingReportModerationRowProps } from '@/types/listing-report'

export default function ListingReportModerationRow({
    listing,
    translations,
    reasonLabels,
}: ListingReportModerationRowProps) {
    const reviewUrl = `/admin/listings/${listing.id}`
    const countTemplate =
        listing.openReportsCount === 1 ? translations.reports_count_one : translations.reports_count_other

    const [ownerBefore, ownerAfter] = translations.owner
        .replace(':email', listing.ownerEmail ?? '')
        .split(':name')

    return (
        <div className="rounded-xl border border-border bg-surface p-4 sm:p-5">
            <div className="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                <div className="flex min-w-0 flex-1 flex-col gap-4 sm:flex-row">
                    <Link
                        href={reviewUrl}
                        className="block aspect-4/3 w-full shrink-0 overflow-hidden rounded-lg sm:w-36"
                    >
                        {listing.imageUrl ? (
                            <img src={listing.imageUrl} alt={listing.title} className="size-full object-cover" />
                        ) : (
                            <ListingImagePlaceholder />
                        )}
                    </Link>

                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <Link href={reviewUrl} className="font-semibold text-text transition-colors hover:text-brand">
                                {listing.title}
                            </Link>
                            <span className="rounded-full bg-status-rejected/10 px-2.5 py-0.5 text-xs font-semibold text-status-rejected">
                                {countTemplate.replace(':count', String(listing.openReportsCount))}
                            </span>
                        </div>

                        <p className="mt-1 text-sm text-text-muted">
                            {ownerBefore}
                            <span className="font-semibold text-text">{listing.ownerName ?? ''}</span>
                            {ownerAfter}
                        </p>

                        <div className="mt-4">
                            <ListingReportList
                                reports={listing.reports}
                                translations={translations}
                                reasonLabels={reasonLabels}
                            />
                        </div>

                        {!listing.canTakeDown && (
                            <p className="mt-3 text-sm text-text-muted">{translations.not_approved}</p>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-3 md:shrink-0 md:flex-col md:items-stretch">
                    <ListingReportActions listing={listing} translations={translations} />
                </div>
            </div>
        </div>
    )
}
