import { Link } from '@inertiajs/react'
import { formatPrice } from '@/Components/Listings/listingFormat'
import StatusBadge from '@/Components/UI/StatusBadge'
import type { AgencyListingRowProps } from '@/types/agency'

export default function AgencyListingRow({ listing, translations, propertyTypeLabels }: AgencyListingRowProps) {
    const isRental = listing.listingType === 'rent'
    const typeLabel = translations.listing_types[listing.listingType] ?? listing.listingType
    const propertyLabel = propertyTypeLabels[listing.propertyType] ?? listing.propertyType
    const createdDate = listing.createdAt
        ? new Date(listing.createdAt).toLocaleDateString('en-US', {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          })
        : null

    return (
        <div className="rounded-xl border border-border bg-surface p-4 sm:p-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <Link
                        href={`/listings/${listing.slug}`}
                        className="font-semibold text-text transition-colors hover:text-brand"
                    >
                        {listing.title}
                    </Link>
                    <p className="mt-1 text-sm text-text-muted">
                        {typeLabel} • {propertyLabel} • {listing.city}, {listing.country}
                    </p>
                    <p className="mt-1 font-bold text-text">
                        {formatPrice(listing.price, listing.currency)}
                        {isRental && (
                            <span className="text-sm font-normal text-text-muted">{translations.per_month}</span>
                        )}
                    </p>
                    {createdDate && (
                        <p className="mt-1 text-xs text-text-muted">
                            {translations.created.replace(':date', createdDate)}
                        </p>
                    )}
                </div>

                <div className="shrink-0">
                    <StatusBadge status={listing.status} label={translations.status[listing.status]} />
                </div>
            </div>

            {listing.status === 'rejected' && listing.rejectionReason && (
                <div className="mt-4 border-t border-border pt-3">
                    <p className="text-xs font-medium uppercase tracking-wide text-text-muted">
                        {translations.rejection_reason_label}
                    </p>
                    <p className="mt-1 text-sm leading-relaxed text-text-soft">{listing.rejectionReason}</p>
                </div>
            )}
        </div>
    )
}
