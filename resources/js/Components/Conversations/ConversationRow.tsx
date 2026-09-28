import { Link } from '@inertiajs/react'
import { ExternalLink } from 'lucide-react'
import { formatRelativeTime } from '@/Components/Conversations/formatRelativeTime'
import type { ConversationRowProps } from '@/types/conversation'

export default function ConversationRow({ conversation, translations }: ConversationRowProps) {
    const hasUnread = conversation.unreadCount > 0
    const isClosed = conversation.listing.status === 'sold' || conversation.listing.status === 'rented'

    return (
        <div
            className={`flex items-start gap-4 rounded-xl border bg-surface p-4 transition-colors hover:border-border-strong ${
                hasUnread ? 'border-brand/40' : 'border-border'
            }`}
        >
            <Link href={`/conversations/${conversation.id}`} className="min-w-0 flex-1">
                <p className={`truncate text-text ${hasUnread ? 'font-bold' : 'font-semibold'}`}>
                    {conversation.listing.title}
                </p>
                <p className="mt-0.5 text-sm text-text-muted">{conversation.otherParticipant.name}</p>
                {conversation.lastMessage && (
                    <p className={`mt-1 truncate text-sm ${hasUnread ? 'font-medium text-text' : 'text-text-soft'}`}>
                        {conversation.lastMessage.body}
                    </p>
                )}
            </Link>

            <div className="flex shrink-0 flex-col items-end gap-2">
                <span className="text-xs text-text-muted">{formatRelativeTime(conversation.updatedAt)}</span>
                {hasUnread && (
                    <span className="rounded-full bg-brand px-2 py-0.5 text-xs font-semibold text-text-inverse">
                        {translations.unread.replace(':count', String(conversation.unreadCount))}
                    </span>
                )}
                {conversation.listing.slug && !isClosed && (
                    <Link
                        href={`/listings/${conversation.listing.slug}`}
                        className="text-text-muted transition-colors hover:text-brand"
                        title={translations.view_listing}
                        aria-label={translations.view_listing}
                    >
                        <ExternalLink className="size-4" />
                    </Link>
                )}
            </div>
        </div>
    )
}
