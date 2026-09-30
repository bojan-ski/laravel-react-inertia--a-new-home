import { router } from '@inertiajs/react'
import { useState } from 'react'

export function useBookmarkToggle(listingId: number) {
    const [processing, setProcessing] = useState(false)

    function toggle() {
        router.post(
            `/listings/${listingId}/bookmark`,
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        )
    }

    return { toggle, processing }
}

export function useStartChat(listingId: number) {
    const [processing, setProcessing] = useState(false)

    function start() {
        router.post(
            `/listings/${listingId}/conversations`,
            {},
            {
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        )
    }

    return { start, processing }
}
