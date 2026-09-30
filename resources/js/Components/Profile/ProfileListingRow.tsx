import { useState } from 'react'
import { router } from '@inertiajs/react'
import ListingCloseActions from '@/Components/Listings/ListingCloseActions'
import { formatDate, formatPrice, listingExpiryText } from '@/Components/Listings/listingFormat'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import ListingRenewButton from '@/Components/Listings/ListingRenewButton'
import Button from '@/Components/UI/Button'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import StatusBadge from '@/Components/UI/StatusBadge'
import type { ListingStatusValue } from '@/types/listing'
import type { ProfileListingRowProps } from '@/types/profile'

const editableStatuses: ListingStatusValue[] = ['pending_review', 'approved', 'hidden']

export default function ProfileListingRow({ listing, translations }: ProfileListingRowProps) {
    const [confirmOpen, setConfirmOpen] = useState(false)
    const [processing, setProcessing] = useState(false)

    const isClosed = listing.status === 'sold' || listing.status === 'rented'
    const isRejected = listing.status === 'rejected'
    const typeLabel = translations.listing_types[listing.listingType] ?? listing.listingType
    const price = `${formatPrice(listing.price, listing.currency)}${
        listing.listingType === 'rent' ? ` ${translations.per_month}` : ''
    }`
    const line = `${listing.city}, ${listing.country} · ${typeLabel} · ${price}`
    const expiryText = listingExpiryText(listing, translations)
    const created = translations.created.replace(':date', formatDate(listing.createdAt))
    const meta = isClosed
        ? translations.closed_on.replace(':date', formatDate(listing.closedAt))
        : [created, expiryText].filter(Boolean).join(' · ')

    function handleDelete() {
        router.delete(`/my-listings/${listing.id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false)
                setConfirmOpen(false)
            },
        })
    }

    const photo = (className: string) => (
        <div className={`relative shrink-0 overflow-hidden bg-sand ${className}`}>
            {listing.imageUrl ? (
                <img
                    src={listing.imageUrl}
                    alt=""
                    className={`absolute inset-0 size-full object-cover ${isClosed ? 'opacity-50' : ''}`}
                />
            ) : (
                <ListingImagePlaceholder />
            )}
        </div>
    )

    const badge = listing.isExpired ? (
        <StatusBadge status="expired" label={translations.expired} />
    ) : (
        <StatusBadge status={listing.status} label={translations.status[listing.status]} />
    )

    const rejectionBox = isRejected && listing.rejectionReason && (
        <p className="bg-sand px-3 py-2.5 text-[13px] leading-normal">
            <strong className="font-medium">{translations.rejection_reason_label}</strong> {listing.rejectionReason}
        </p>
    )

    const actions = !isRejected && (
        <>
            {editableStatuses.includes(listing.status) && (
                <Button variant="strong-link" href={`/my-listings/${listing.id}/edit`} className="text-sm">
                    {translations.edit}
                </Button>
            )}
            <ListingCloseActions listing={listing} baseUrl="/my-listings" translations={translations} />
            <Button variant="link" onClick={() => setConfirmOpen(true)}>
                {translations.delete}
            </Button>
        </>
    )

    const renew = <ListingRenewButton listing={listing} baseUrl="/my-listings" translations={translations} />

    return (
        <li className="border-b border-rule">
            <div className="hidden grid-cols-[180px_minmax(0,1fr)_280px_300px] items-center gap-10 py-7 xl:grid">
                {photo('aspect-3/2')}

                <div className="flex min-w-0 flex-col gap-2">
                    <p className="text-[26px] leading-tight font-light tracking-[-0.015em] wrap-break-word">
                        {listing.title}
                    </p>
                    <p className="text-sm text-ink-muted">{line}</p>
                    {rejectionBox && <div className="mt-1">{rejectionBox}</div>}
                </div>

                <div className="flex flex-col items-start gap-2.5">
                    {badge}
                    <p className="text-[13px] text-ink-muted">{meta}</p>
                    {renew}
                </div>

                <div className="flex items-center justify-end gap-5">{actions}</div>
            </div>

            <div className="flex flex-col gap-3 py-6 xl:hidden">
                <div className="grid grid-cols-[96px_minmax(0,1fr)] gap-3.5">
                    {photo('h-18 w-24')}
                    <div className="flex min-w-0 flex-col items-start gap-1.5">
                        {badge}
                        <p className="text-base leading-tight wrap-break-word">{listing.title}</p>
                    </div>
                </div>
                <p className="text-[13px] text-ink-muted">{line}</p>
                <p className="text-[13px] text-ink-muted">{meta}</p>
                {rejectionBox}
                {(actions || listing.canRenew) && (
                    <div className="flex min-h-8 flex-wrap items-center gap-x-5 gap-y-2">
                        {actions}
                        {renew}
                    </div>
                )}
            </div>

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
        </li>
    )
}
