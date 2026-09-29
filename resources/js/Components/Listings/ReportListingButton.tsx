import { Flag } from 'lucide-react'
import { useState } from 'react'
import ReportListingDialog from '@/Components/Listings/ReportListingDialog'
import type { ReportListingButtonProps } from '@/types/listing-report'

export default function ReportListingButton({ listingId, translations }: ReportListingButtonProps) {
    const [open, setOpen] = useState(false)

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="inline-flex items-center gap-1.5 text-xs text-text-muted transition-colors hover:text-status-rejected"
            >
                <Flag className="size-3.5" />
                {translations.action}
            </button>

            <ReportListingDialog
                open={open}
                onClose={() => setOpen(false)}
                listingId={listingId}
                translations={translations}
            />
        </>
    )
}
