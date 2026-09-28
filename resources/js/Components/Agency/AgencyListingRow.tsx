import { useState } from 'react'
import { Link, router } from '@inertiajs/react'
import { Trash2 } from 'lucide-react'
import ListingCloseActions from '@/Components/Listings/ListingCloseActions'
import ListingExpiryNote from '@/Components/Listings/ListingExpiryNote'
import { formatDate, formatPrice } from '@/Components/Listings/listingFormat'
import ListingRenewButton from '@/Components/Listings/ListingRenewButton'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import StatusBadge from '@/Components/UI/StatusBadge'
import type { AgencyListingRowProps } from '@/types/agency'

export default function AgencyListingRow({ listing, translations, propertyTypeLabels }: AgencyListingRowProps) {
    const [confirmOpen, setConfirmOpen] = useState(false)
    const [processing, setProcessing] = useState(false)

    const isRental = listing.listingType === 'rent'
    const typeLabel = translations.listing_types[listing.listingType] ?? listing.listingType
    const propertyLabel = propertyTypeLabels[listing.propertyType] ?? listing.propertyType
    const createdDate = formatDate(listing.createdAt)
    const isClosed = listing.status === 'sold' || listing.status === 'rented'
    const closedDate = isClosed && listing.closedAt ? formatDate(listing.closedAt) : null

    function handleDelete() {
        router.delete(`/agency/listings/${listing.id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false)
                setConfirmOpen(false)
            },
        })
    }

    return (
        <div className="rounded-xl border border-border bg-surface p-4 sm:p-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    {isClosed ? (
                        <p className="font-semibold text-text">{listing.title}</p>
                    ) : (
                        <Link
                            href={`/agency/listings/${listing.id}/edit`}
                            className="font-semibold text-text transition-colors hover:text-brand"
                        >
                            {listing.title}
                        </Link>
                    )}
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
                            {closedDate && ` • ${translations.closed_on.replace(':date', closedDate)}`}
                        </p>
                    )}
                    <ListingExpiryNote listing={listing} translations={translations} />
                </div>

                <div className="flex items-center justify-between gap-3 sm:flex-col sm:items-end">
                    {listing.isExpired ? (
                        <StatusBadge status="expired" label={translations.expired} />
                    ) : (
                        <StatusBadge status={listing.status} label={translations.status[listing.status]} />
                    )}
                    <div className="flex items-center gap-2">
                        <ListingRenewButton listing={listing} baseUrl="/agency/listings" translations={translations} />
                        <ListingCloseActions listing={listing} baseUrl="/agency/listings" translations={translations} />
                        <button
                            type="button"
                            onClick={() => setConfirmOpen(true)}
                            aria-label={translations.delete}
                            className="rounded-lg p-2 text-text-muted transition-colors hover:bg-status-rejected/10 hover:text-status-rejected"
                        >
                            <Trash2 className="size-4" />
                        </button>
                    </div>
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

            <ConfirmDialog
                open={confirmOpen}
                title={translations.confirm.title}
                description={translations.confirm.description.replace(':title', listing.title)}
                confirmLabel={translations.confirm.confirm}
                cancelLabel={translations.confirm.cancel}
                processing={processing}
                onConfirm={handleDelete}
                onCancel={() => setConfirmOpen(false)}
            />
        </div>
    )
}
