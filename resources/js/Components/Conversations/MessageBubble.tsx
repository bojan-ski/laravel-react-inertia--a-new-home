import { Flag } from 'lucide-react'
import type { MessageBubbleProps } from '@/types/conversation'

function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
}

export default function MessageBubble({ message, isOwn, showName, reportLabel, onReport }: MessageBubbleProps) {
    return (
        <div className={`group flex flex-col ${isOwn ? 'items-end' : 'items-start'}`}>
            {showName && !isOwn && (
                <span className="mb-1 px-1 text-xs font-medium text-text-muted">{message.senderName}</span>
            )}

            <div className="flex max-w-[85%] items-center gap-2 sm:max-w-[75%]">
                <div
                    className={`min-w-0 rounded-2xl px-4 py-2 text-sm ${
                        isOwn ? 'bg-brand text-text-inverse' : 'bg-surface-muted text-text'
                    }`}
                >
                    <p className="whitespace-pre-wrap wrap-break-word">{message.body}</p>
                </div>

                {!isOwn && onReport && (
                    <button
                        type="button"
                        onClick={onReport}
                        aria-label={reportLabel}
                        className="inline-flex shrink-0 items-center gap-1 text-xs text-text-muted opacity-100 transition-colors hover:text-status-rejected md:opacity-0 md:group-hover:opacity-100"
                    >
                        <Flag className="size-3.5" />
                        <span className="sr-only sm:not-sr-only">{reportLabel}</span>
                    </button>
                )}
            </div>

            <span className="mt-1 px-1 text-xs text-text-muted">{formatTime(message.createdAt)}</span>
        </div>
    )
}
