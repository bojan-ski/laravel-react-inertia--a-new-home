import { useState } from 'react'
import { router } from '@inertiajs/react'
import RejectListingDialog from '@/Components/Admin/RejectListingDialog'
import type { ListingModerationActionsProps } from '@/types/admin'

export default function ListingModerationActions({
    listingId,
    listingTitle,
    translations,
}: ListingModerationActionsProps) {
    const [rejectOpen, setRejectOpen] = useState(false)
    const [processing, setProcessing] = useState(false)

    function approve() {
        router.post(`/admin/listings/${listingId}/approve`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    return (
        <>
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

            <RejectListingDialog
                open={rejectOpen}
                onClose={() => setRejectOpen(false)}
                listingId={listingId}
                listingTitle={listingTitle}
                translations={translations}
            />
        </>
    )
}
