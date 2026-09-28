import { useState } from 'react'
import { router } from '@inertiajs/react'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import type { ListingCloseActionsProps } from '@/types/listing'

export default function ListingCloseActions({ listing, baseUrl, translations }: ListingCloseActionsProps) {
    const [confirmOpen, setConfirmOpen] = useState(false)
    const [processing, setProcessing] = useState(false)

    const isClosed = listing.status === 'sold' || listing.status === 'rented'

    if (listing.status !== 'approved' && !isClosed) {
        return null
    }

    const isRental = listing.listingType === 'rent'
    const confirm = isClosed ? translations.reopen_confirm : translations.close_confirm[isRental ? 'rent' : 'sale']
    const buttonLabel = isClosed ? translations.reopen : isRental ? translations.mark_rented : translations.mark_sold

    function handleConfirm() {
        router.post(
            `${baseUrl}/${listing.id}/${isClosed ? 'reopen' : 'close'}`,
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => {
                    setProcessing(false)
                    setConfirmOpen(false)
                },
            },
        )
    }

    return (
        <>
            <button
                type="button"
                onClick={() => setConfirmOpen(true)}
                className="rounded-lg border border-border-strong px-3 py-1.5 text-sm font-medium text-text-soft transition-colors hover:border-brand hover:text-brand"
            >
                {buttonLabel}
            </button>

            <ConfirmDialog
                open={confirmOpen}
                title={confirm.title}
                description={confirm.description.replace(':title', listing.title)}
                confirmLabel={confirm.confirm}
                cancelLabel={confirm.cancel}
                processing={processing}
                tone="brand"
                onConfirm={handleConfirm}
                onCancel={() => setConfirmOpen(false)}
            />
        </>
    )
}
