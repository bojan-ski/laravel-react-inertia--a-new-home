import { Link } from '@inertiajs/react'
import { ExternalLink } from 'lucide-react'
import { formatRelativeTime } from '@/Components/Conversations/formatRelativeTime'
import type { ConversationRowProps } from '@/types/conversation'

export default function ConversationRow({ conversation, translations }: ConversationRowProps) {
    return (
        <div className="flex items-start gap-4 rounded-xl border border-border bg-surface p-4 transition-colors hover:border-border-strong">
            <Link href={`/conversations/${conversation.id}`} className="min-w-0 flex-1">
                <p className="truncate font-semibold text-text">{conversation.listing.title}</p>
                <p className="mt-0.5 text-sm text-text-muted">{conversation.otherParticipant.name}</p>
                {conversation.lastMessage && (
                    <p className="mt-1 truncate text-sm text-text-soft">{conversation.lastMessage.body}</p>
                )}
            </Link>

            <div className="flex shrink-0 flex-col items-end gap-2">
                <span className="text-xs text-text-muted">{formatRelativeTime(conversation.updatedAt)}</span>
                {conversation.listing.slug && (
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
