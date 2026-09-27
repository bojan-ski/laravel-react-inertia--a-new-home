import { usePage } from '@inertiajs/react'
import { useState } from 'react'
import FlashBanner from '@/Components/UI/FlashBanner'
import type { SharedProps } from '@/types/shared'

export default function ThrottleNotice() {
    const message = usePage<SharedProps>().props.flash?.throttled ?? null
    const [dismissed, setDismissed] = useState<string | null>(null)

    if (!message || dismissed === message) {
        return null
    }

    return (
        <div role="alert" className="fixed inset-x-4 top-4 z-50 mx-auto max-w-md rounded-lg bg-surface shadow-lg">
            <FlashBanner message={message} variant="error" className="" onDismiss={() => setDismissed(message)} />
        </div>
    )
}
