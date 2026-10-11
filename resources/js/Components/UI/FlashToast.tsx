import { usePage } from '@inertiajs/react'
import { useEffect, useState } from 'react'
import FlashBanner from '@/Components/UI/FlashBanner'
import type { FlashData, SharedProps } from '@/types/shared'
import type { FlashToastMessage } from '@/types/ui'

const SUCCESS_TIMEOUT_MS = 6000

let nextToastId = 0

function toastFor(flash: FlashData | undefined): FlashToastMessage | null {
    if (flash?.error) {
        return { id: ++nextToastId, message: flash.error, variant: 'error' }
    }

    if (flash?.throttled) {
        return { id: ++nextToastId, message: flash.throttled, variant: 'info' }
    }

    if (flash?.success) {
        return { id: ++nextToastId, message: flash.success, variant: 'success' }
    }

    return null
}

export default function FlashToast() {
    const { flash, ui } = usePage<SharedProps>().props
    const [seenFlash, setSeenFlash] = useState(flash)
    const [toast, setToast] = useState(() => toastFor(flash))

    if (flash !== seenFlash) {
        setSeenFlash(flash)

        const next = toastFor(flash)

        if (next) {
            setToast(next)
        }
    }

    useEffect(() => {
        if (toast?.variant !== 'success') {
            return
        }

        const timer = window.setTimeout(() => setToast(null), SUCCESS_TIMEOUT_MS)

        return () => window.clearTimeout(timer)
    }, [toast])

    return (
        <div className="pointer-events-none fixed inset-x-4 top-4 z-60 flex justify-end sm:inset-x-auto sm:top-6 sm:right-6">
            {toast && (
                <FlashBanner
                    key={toast.id}
                    message={toast.message}
                    variant={toast.variant}
                    className="pointer-events-auto w-full shadow-lg sm:w-95"
                    onDismiss={() => setToast(null)}
                    dismissLabel={ui.dismiss}
                />
            )}
        </div>
    )
}
