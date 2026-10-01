import { useState } from 'react'
import { router } from '@inertiajs/react'
import { notLiveNoticeId } from '@/Components/Admin/listingReportIds'
import RejectAgencyDialog from '@/Components/Admin/RejectAgencyDialog'
import Button from '@/Components/UI/Button'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { ListingReportActionsProps } from '@/types/listing-report'

export default function ListingReportActions({ listing, translations }: ListingReportActionsProps) {
    const [takeDownOpen, setTakeDownOpen] = useState(false)
    const [processing, setProcessing] = useState(false)
    const actionLabel = useActionLabel()

    function dismiss() {
        router.post(`/admin/listing-reports/${listing.id}/dismiss`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    return (
        <>
            <Button
                variant="secondary"
                size="sm"
                onClick={dismiss}
                disabled={processing}
                aria-label={actionLabel(translations.actions.dismiss, listing.title)}
            >
                {translations.actions.dismiss}
            </Button>
            <Button
                size="sm"
                onClick={() => setTakeDownOpen(true)}
                aria-label={actionLabel(translations.actions.take_down, listing.title)}
                disabled={processing || !listing.canTakeDown}
                aria-describedby={listing.canTakeDown ? undefined : notLiveNoticeId(listing.id)}
            >
                {translations.actions.take_down}
            </Button>

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
