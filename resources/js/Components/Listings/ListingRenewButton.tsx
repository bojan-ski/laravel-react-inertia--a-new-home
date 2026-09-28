import { useState } from 'react'
import { router } from '@inertiajs/react'
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
        <button
            type="button"
            onClick={handleRenew}
            disabled={processing}
            className="rounded-lg bg-brand px-3 py-1.5 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-60"
        >
            {translations.renew}
        </button>
    )
}
