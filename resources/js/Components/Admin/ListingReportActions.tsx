import { useState } from 'react'
import { router } from '@inertiajs/react'
import RejectAgencyDialog from '@/Components/Admin/RejectAgencyDialog'
import type { ListingReportActionsProps } from '@/types/listing-report'

export default function ListingReportActions({ listing, translations }: ListingReportActionsProps) {
    const [takeDownOpen, setTakeDownOpen] = useState(false)
    const [processing, setProcessing] = useState(false)

    function dismiss() {
        router.post(`/admin/listing-reports/${listing.id}/dismiss`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    return (
        <>
            <button
                type="button"
                onClick={dismiss}
                disabled={processing}
                className="rounded-lg border border-border-strong px-4 py-2 text-sm font-semibold text-text transition-colors hover:border-text disabled:opacity-50"
            >
                {translations.actions.dismiss}
            </button>
            {listing.canTakeDown && (
                <button
                    type="button"
                    onClick={() => setTakeDownOpen(true)}
                    disabled={processing}
                    className="rounded-lg border border-status-rejected px-4 py-2 text-sm font-semibold text-status-rejected transition-colors hover:bg-status-rejected/10 disabled:opacity-50"
                >
                    {translations.actions.take_down}
                </button>
            )}

            <RejectAgencyDialog
                open={takeDownOpen}
                onClose={() => setTakeDownOpen(false)}
                actionUrl={`/admin/listing-reports/${listing.id}/take-down`}
                subjectName={listing.title}
                dialog={translations.dialog}
            />
        </>
    )
}
