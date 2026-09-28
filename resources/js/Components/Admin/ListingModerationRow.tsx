import { Link } from '@inertiajs/react'
import ListingModerationActions from '@/Components/Admin/ListingModerationActions'
import { formatDateTime, formatPrice } from '@/Components/Listings/listingFormat'
import type { AdminListingModerationRowProps } from '@/types/admin'

export default function ListingModerationRow({
    listing,
    translations,
    propertyTypeLabels,
}: AdminListingModerationRowProps) {
    const reviewUrl = `/admin/listings/${listing.id}`

    const metaLine = [
        translations.listing_types[listing.listingType],
        propertyTypeLabels[listing.propertyType] ?? listing.propertyType,
        `${listing.city}, ${listing.country}`,
    ].join(' • ')

    const [ownerBefore, ownerAfter] = translations.owner
        .replace(':email', listing.ownerEmail ?? '')
        .split(':name')

    return (
        <div className="rounded-xl border border-border bg-surface p-4 sm:p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div className="min-w-0">
                    <Link href={reviewUrl} className="font-semibold text-text transition-colors hover:text-brand">
                        {listing.title}
                    </Link>
                    <p className="mt-0.5 text-sm text-text-muted">{metaLine}</p>

                    <p className="mt-2 font-bold text-text">
                        {formatPrice(listing.price, listing.currency)}
                        {listing.listingType === 'rent' && (
                            <span className="text-sm font-normal text-text-muted">
                                {translations.per_month}
                            </span>
                        )}
                    </p>

                    <p className="mt-2 text-sm text-text-muted">
                        {ownerBefore}
                        <span className="font-semibold text-text">{listing.ownerName ?? ''}</span>
                        {ownerAfter}
                    </p>
                    <p className="mt-0.5 text-sm text-text-muted">
                        {translations.submitted.replace(':date', formatDateTime(listing.createdAt))}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3 md:shrink-0">
                    <Link
                        href={reviewUrl}
                        className="rounded-lg border border-border-strong px-4 py-2 text-sm font-semibold text-text transition-colors hover:border-text"
                    >
                        {translations.view_listing}
                    </Link>
                    <ListingModerationActions
                        listingId={listing.id}
                        listingTitle={listing.title}
                        translations={translations}
                    />
                </div>
            </div>
        </div>
    )
}
