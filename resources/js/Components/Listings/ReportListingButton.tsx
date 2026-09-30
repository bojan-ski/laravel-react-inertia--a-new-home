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
                className="inline-flex min-h-8 items-center self-start text-[13px] text-ink/60 transition-colors duration-150 hover:text-forest"
            >
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
