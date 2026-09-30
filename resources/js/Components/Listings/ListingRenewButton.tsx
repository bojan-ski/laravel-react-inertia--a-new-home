import { useState } from 'react'
import { router } from '@inertiajs/react'
import Button from '@/Components/UI/Button'
import type { ListingRenewButtonProps } from '@/types/listing'

export default function ListingRenewButton({ listing, baseUrl, translations }: ListingRenewButtonProps) {
    const [processing, setProcessing] = useState(false)

    if (!listing.canRenew) {
        return null
    }

    function handleRenew() {
        router.post(
            `${baseUrl}/${listing.id}/renew`,
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        )
    }

    return (
        <Button variant="secondary" size="sm" onClick={handleRenew} disabled={processing} className="px-3.5! text-[13px]!">
            {translations.renew}
        </Button>
    )
}
