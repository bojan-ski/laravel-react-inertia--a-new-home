import { useState } from 'react'
import { router } from '@inertiajs/react'
import Button from '@/Components/UI/Button'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { ListingCloseActionsProps } from '@/types/listing'

export default function ListingCloseActions({ listing, baseUrl, translations }: ListingCloseActionsProps) {
    const [confirmOpen, setConfirmOpen] = useState(false)
    const [processing, setProcessing] = useState(false)
    const actionLabel = useActionLabel()

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
            <Button
                variant={isClosed ? 'strong-link' : 'link'}
                onClick={() => setConfirmOpen(true)}
                aria-label={actionLabel(buttonLabel, listing.title)}
                className={isClosed ? 'text-sm' : undefined}
            >
                {buttonLabel}
            </Button>

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
