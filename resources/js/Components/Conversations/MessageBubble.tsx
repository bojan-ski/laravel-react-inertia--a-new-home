import type { MessageBubbleProps } from '@/types/conversation'

function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
}

export default function MessageBubble({ message, isOwn, showName }: MessageBubbleProps) {
    return (
        <div className={`flex flex-col ${isOwn ? 'items-end' : 'items-start'}`}>
            {showName && !isOwn && (
                <span className="mb-1 px-1 text-xs font-medium text-text-muted">{message.senderName}</span>
            )}

            <div
                className={`max-w-[75%] rounded-2xl px-4 py-2 text-sm sm:max-w-[65%] ${
                    isOwn ? 'bg-brand text-text-inverse' : 'bg-surface-muted text-text'
                }`}
            >
                <p className="whitespace-pre-wrap break-words">{message.body}</p>
            </div>

            <span className="mt-1 px-1 text-xs text-text-muted">{formatTime(message.createdAt)}</span>
        </div>
    )
}
