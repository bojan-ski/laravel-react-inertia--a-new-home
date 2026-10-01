import { useState } from 'react'
import { router } from '@inertiajs/react'
import ListingCloseActions from '@/Components/Listings/ListingCloseActions'
import { formatDate, formatPrice, listingExpiryText } from '@/Components/Listings/listingFormat'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import { LISTING_THUMB_SIZE } from '@/Components/Listings/listingImageSize'
import ListingRenewButton from '@/Components/Listings/ListingRenewButton'
import Button from '@/Components/UI/Button'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import StatusBadge from '@/Components/UI/StatusBadge'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { AgencyListingRowProps } from '@/types/agency'
import type { ListingStatusValue } from '@/types/listing'

const editableStatuses: ListingStatusValue[] = ['pending_review', 'approved', 'hidden']

export default function AgencyListingRow({ listing, translations }: AgencyListingRowProps) {
    const [confirmOpen, setConfirmOpen] = useState(false)
    const [processing, setProcessing] = useState(false)
    const actionLabel = useActionLabel()

    const isClosed = listing.status === 'sold' || listing.status === 'rented'
    const isRejected = listing.status === 'rejected'
    const typeLabel = translations.listing_types[listing.listingType] ?? listing.listingType
    const price = `${formatPrice(listing.price, listing.currency)}${
        listing.listingType === 'rent' ? ` ${translations.per_month}` : ''
    }`
    const line = `${listing.city} · ${typeLabel} · ${price}`
    const meta = isClosed
        ? translations.closed_on.replace(':date', formatDate(listing.closedAt))
        : listing.status === 'approved'
          ? [
                translations.approved.replace(':date', formatDate(listing.approvedAt)),
                listingExpiryText(listing, translations),
            ]
                .filter(Boolean)
                .join(' · ')
          : translations.created.replace(':date', formatDate(listing.createdAt))

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

    const photo = (className: string) => (
        <div className={`relative shrink-0 overflow-hidden bg-sand ${className}`}>
            {listing.imageUrl ? (
                <img
                    src={listing.imageUrl}
                    alt=""
                    width={LISTING_THUMB_SIZE.width}
                    height={LISTING_THUMB_SIZE.height}
                    loading="lazy"
                    decoding="async"
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
                <Button
                    variant="strong-link"
                    href={`/agency/listings/${listing.id}/edit`}
                    aria-label={actionLabel(translations.edit, listing.title)}
                    className="text-sm"
                >
                    {translations.edit}
                </Button>
            )}
            <ListingCloseActions listing={listing} baseUrl="/agency/listings" translations={translations} />
            <Button
                variant="link"
                onClick={() => setConfirmOpen(true)}
                aria-label={actionLabel(translations.delete, listing.title)}
            >
                {translations.delete}
            </Button>
        </>
    )

    const renew = <ListingRenewButton listing={listing} baseUrl="/agency/listings" translations={translations} />

    return (
        <li className="border-b border-rule">
            <div className="hidden grid-cols-[140px_minmax(0,1fr)_230px] items-center gap-7 py-5.5 md:grid">
                {photo('aspect-3/2')}

                <div className="flex min-w-0 flex-col items-start gap-1.5">
                    {badge}
                    <p className="text-[22px] leading-tight font-light tracking-[-0.01em] wrap-break-word">
                        {listing.title}
                    </p>
                    <p className="text-[13px] text-ink-muted">{line}</p>
                    <p className="text-[13px] text-ink-muted">{meta}</p>
                    {rejectionBox && <div className="mt-1">{rejectionBox}</div>}
                </div>

                <div className="flex flex-col items-end gap-3">
                    {actions && <div className="flex flex-wrap justify-end gap-x-4.5 gap-y-2">{actions}</div>}
                    {renew}
                </div>
            </div>

            <div className="flex flex-col gap-2.5 py-5 md:hidden">
                <div className="grid grid-cols-[88px_minmax(0,1fr)] gap-3.5">
                    {photo('h-16.5 w-22')}
                    <div className="flex min-w-0 flex-col items-start gap-1.5">
                        {badge}
                        <p className="text-[15px] leading-tight wrap-break-word">{listing.title}</p>
                    </div>
                </div>
                <p className="text-[13px] text-ink-muted">{`${line} · ${meta}`}</p>
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
