import { useId } from 'react'
import { formatMessageTime } from '@/Components/Conversations/chatTime'
import type { MessageBubbleProps } from '@/types/conversation'

export default function MessageBubble({ message, isOwn, showName, reportLabel, onReport }: MessageBubbleProps) {
    const bodyId = useId()
    const time = <time dateTime={message.createdAt}>{formatMessageTime(message.createdAt)}</time>

    if (isOwn) {
        return (
            <div className="flex max-w-[82%] flex-col items-end gap-1 self-end lg:max-w-[62%] lg:gap-1.5">
                {showName && <span className="sr-only">{message.senderName}</span>}
                <p className="bg-forest-50 px-3.5 py-3 text-sm/normal whitespace-pre-wrap text-forest-900 wrap-anywhere lg:px-4.5 lg:py-3.5 lg:text-[15px]/[1.55]">
                    {message.body}
                </p>
                <span className="text-[11px] text-ink/55 lg:text-xs">{time}</span>
            </div>
        )
    }

    return (
        <div className="flex max-w-[82%] flex-col gap-1 self-start lg:max-w-[62%] lg:gap-1.5">
            {showName && <span className="sr-only">{message.senderName}</span>}
            <p id={bodyId} className="border-l border-ink py-0.5 pl-3.5 text-sm/normal font-book whitespace-pre-wrap wrap-break-word lg:py-1 lg:pl-4.5 lg:text-[15px]/[1.55]">
                {message.body}
            </p>
            <div className="flex gap-4 pl-3.75 text-[11px] text-ink/55 lg:pl-4.75 lg:text-xs">
                {time}
                {onReport && (
                    <button
                        type="button"
                        onClick={onReport}
                        aria-describedby={bodyId}
                        className="transition-colors duration-150 hover:text-forest"
                    >
                        {reportLabel}
                    </button>
                )}
            </div>
        </div>
    )
}
