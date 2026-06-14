import { useState } from 'react'
import { router } from '@inertiajs/react'
import RejectListingDialog from '@/Components/Admin/RejectListingDialog'
import { formatPrice } from '@/Components/Listings/listingFormat'
import type { AdminListingModerationRowProps } from '@/types/admin'

function formatSubmitted(value: string | null): string {
    if (!value) {
        return ''
    }

    return new Date(value).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    })
}

export default function ListingModerationRow({
    listing,
    translations,
    propertyTypeLabels,
}: AdminListingModerationRowProps) {
    const [rejectOpen, setRejectOpen] = useState(false)
    const [processing, setProcessing] = useState(false)

    function approve() {
        router.post(`/admin/listings/${listing.id}/approve`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

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
                    <p className="font-semibold text-text">{listing.title}</p>
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
                        {translations.submitted.replace(':date', formatSubmitted(listing.createdAt))}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3 md:shrink-0">
                    <button
                        type="button"
                        onClick={approve}
                        disabled={processing}
                        className="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-50"
                    >
                        {translations.actions.approve}
                    </button>
                    <button
                        type="button"
                        onClick={() => setRejectOpen(true)}
                        disabled={processing}
                        className="rounded-lg border border-status-rejected px-4 py-2 text-sm font-semibold text-status-rejected transition-colors hover:bg-status-rejected/10 disabled:opacity-50"
                    >
                        {translations.actions.reject}
                    </button>
                </div>
            </div>

            <RejectListingDialog
                open={rejectOpen}
                onClose={() => setRejectOpen(false)}
                listingId={listing.id}
                listingTitle={listing.title}
                translations={translations}
            />
        </div>
    )
}
