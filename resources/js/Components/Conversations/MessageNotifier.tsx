import { useEcho } from '@laravel/echo-react'
import { Link, router, usePage } from '@inertiajs/react'
import { MessageCircle, X } from 'lucide-react'
import { useEffect, useState } from 'react'
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
            className="fixed inset-x-4 bottom-4 z-50 rounded-2xl border border-border bg-surface p-4 shadow-lg sm:inset-x-auto sm:right-6 sm:bottom-6 sm:w-96"
        >
            <div className="flex items-start gap-3">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-soft text-brand">
                    <MessageCircle className="size-4" />
                </span>

                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-text">
                        {messageNotifications.title.replace(':name', toast.senderName)}
                    </p>
                    {toast.listingTitle && <p className="truncate text-xs text-text-muted">{toast.listingTitle}</p>}
                    <p className="mt-1 line-clamp-2 text-sm text-text-soft">{toast.preview}</p>

                    <Link
                        href={`/conversations/${toast.conversationId}`}
                        onClick={() => setToast(null)}
                        className="mt-2 inline-block text-sm font-semibold text-brand transition-colors hover:text-brand-dark"
                    >
                        {messageNotifications.view}
                    </Link>
                </div>

                <button
                    type="button"
                    onClick={() => setToast(null)}
                    aria-label={messageNotifications.dismiss}
                    className="text-text-muted transition-colors hover:text-text"
                >
                    <X className="size-4" />
                </button>
            </div>
        </div>
    )
}
