import { useEcho } from '@laravel/echo-react'
import { Link, router, usePage } from '@inertiajs/react'
import { useEffect, useState } from 'react'
import Kicker from '@/Components/UI/Kicker'
import type { ChatMessageReceivedBroadcast, MessageNotifierProps } from '@/types/conversation'
import type { SharedProps } from '@/types/shared'

const TOAST_DURATION_MS = 8000

export default function MessageNotifier({ userId }: MessageNotifierProps) {
    const { messageNotifications } = usePage<SharedProps>().props
    const [toast, setToast] = useState<ChatMessageReceivedBroadcast | null>(null)

    useEcho<ChatMessageReceivedBroadcast>(`App.Models.User.${userId}`, '.ChatMessageReceived', (event) => {
        router.reload({ only: ['unreadMessages', 'conversations'] })

        if (window.location.pathname !== `/conversations/${event.conversationId}`) {
            setToast(event)
        }
    })

    useEffect(() => {
        if (!toast) {
            return
        }

        const timeout = window.setTimeout(() => setToast(null), TOAST_DURATION_MS)

        return () => window.clearTimeout(timeout)
    }, [toast])

    if (!toast || !messageNotifications) {
        return null
    }

    return (
        <div
            role="status"
            aria-live="polite"
            className="fixed inset-x-4 bottom-4 z-50 flex flex-col gap-1.5 border-l-2 border-forest bg-paper px-5 py-4.5 shadow-lg sm:inset-x-auto sm:right-6 sm:bottom-6 sm:w-95"
        >
            <Kicker as="p">{messageNotifications.title.replace(':name', toast.senderName)}</Kicker>
            {toast.listingTitle && <p className="truncate text-[13px] text-ink-muted">{toast.listingTitle}</p>}
            <p className="line-clamp-2 text-sm/normal text-ink">{toast.preview}</p>

            <div className="mt-1 flex gap-5 text-sm">
                <Link
                    href={`/conversations/${toast.conversationId}`}
                    onClick={() => setToast(null)}
                    className="border-b border-current text-forest transition-colors duration-150 hover:text-forest-600"
                >
                    {messageNotifications.view}
                </Link>
                <button
                    type="button"
                    onClick={() => setToast(null)}
                    className="text-ink-muted transition-colors duration-150 hover:text-forest"
                >
                    {messageNotifications.dismiss}
                </button>
            </div>
        </div>
    )
}
