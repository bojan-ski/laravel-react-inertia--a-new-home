import { useState } from 'react'
import { router } from '@inertiajs/react'
import RejectListingDialog from '@/Components/Admin/RejectListingDialog'
import Button from '@/Components/UI/Button'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { ListingModerationActionsProps } from '@/types/admin'

export default function ListingModerationActions({
    listingId,
    listingTitle,
    translations,
    layout = 'inline',
}: ListingModerationActionsProps) {
    const [rejectOpen, setRejectOpen] = useState(false)
    const [processing, setProcessing] = useState(false)
    const actionLabel = useActionLabel()

    function approve() {
        router.post(`/admin/listings/${listingId}/approve`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    return (
        <div className={layout === 'stacked' ? 'grid grid-cols-2 gap-2.5' : 'flex items-center gap-2.5'}>
            <Button
                variant="secondary"
                size="sm"
                onClick={() => setRejectOpen(true)}
                disabled={processing}
                aria-label={actionLabel(translations.actions.reject, listingTitle)}
            >
                {translations.actions.reject}
            </Button>
            <Button
                size="sm"
                onClick={approve}
                disabled={processing}
                aria-label={actionLabel(translations.actions.approve, listingTitle)}
            >
                {translations.actions.approve}
            </Button>

            <RejectListingDialog
                open={rejectOpen}
                onClose={() => setRejectOpen(false)}
                listingId={listingId}
                listingTitle={listingTitle}
                translations={translations}
            />
        </div>
    )
}
