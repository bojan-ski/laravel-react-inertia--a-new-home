import { usePage } from '@inertiajs/react'
import { useState } from 'react'
import FlashBanner from '@/Components/UI/FlashBanner'
import type { SharedProps } from '@/types/shared'

export default function ThrottleNotice() {
    const { flash, ui } = usePage<SharedProps>().props
    const message = flash?.throttled ?? null
    const [dismissed, setDismissed] = useState<string | null>(null)

    if (!message || dismissed === message) {
        return null
    }

    return (
        <div role="alert" className="fixed inset-x-4 top-4 z-50 mx-auto max-w-130 shadow-lg">
            <FlashBanner
                message={message}
                variant="info"
                className=""
                onDismiss={() => setDismissed(message)}
                dismissLabel={ui.dismiss}
            />
        </div>
    )
}
