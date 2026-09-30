import { Link } from '@inertiajs/react'
import ConversationThumbnail from '@/Components/Conversations/ConversationThumbnail'
import { formatInboxTime } from '@/Components/Conversations/chatTime'
import { conversationListingLine } from '@/Components/Conversations/conversationListing'
import type { ConversationRowProps } from '@/types/conversation'

export default function ConversationRow({ conversation, translations, currentUserId, selected }: ConversationRowProps) {
    const hasUnread = conversation.unreadCount > 0
    const lastMessage = conversation.lastMessage
    const isOwnLastMessage = lastMessage !== null && currentUserId !== null && lastMessage.senderId === currentUserId

    return (
        <li>
            <Link
                href={`/conversations/${conversation.id}`}
                aria-current={selected ? 'page' : undefined}
                className={`grid grid-cols-[56px_minmax(0,1fr)_auto] gap-3.5 border-t focus-visible:-outline-offset-2 border-rule py-4 pr-5 pl-5 transition-colors duration-150 md:pr-10 md:pl-10 lg:grid-cols-[64px_minmax(0,1fr)_auto] lg:gap-4 lg:py-4.5 lg:pr-8 xl:pl-16 ${
                    selected ? 'bg-forest-50' : 'hover:bg-forest-50/70'
                }`}
            >
                <ConversationThumbnail listing={conversation.listing} className="size-14 lg:size-16" />

                <div className="flex min-w-0 flex-col gap-0.5 lg:gap-0.75">
                    <p className={`truncate text-[15px] ${hasUnread ? 'font-semibold' : ''}`}>
                        {conversation.otherParticipant.name}
                    </p>
                    <p className="truncate text-xs text-ink-muted lg:text-[13px]">
                        {conversationListingLine(conversation.listing, translations)}
                    </p>
                    {lastMessage && (
                        <p className="mt-0.5 truncate text-[13px] font-book lg:text-sm">
                            {isOwnLastMessage && `${translations.you_prefix} `}
                            {lastMessage.body}
                        </p>
                    )}
                </div>

                <div className="flex flex-col items-end gap-1.5 lg:gap-2">
                    <span className="text-[11px] whitespace-nowrap text-ink/60 lg:text-xs">
                        {formatInboxTime(lastMessage?.createdAt ?? conversation.updatedAt, translations.yesterday)}
                    </span>
                    {hasUnread && (
                        <span className="bg-forest px-1.5 py-0.5 text-[11px] whitespace-nowrap text-paper lg:px-1.75">
                            {translations.unread.replace(':count', String(conversation.unreadCount))}
                        </span>
                    )}
                </div>
            </Link>
        </li>
    )
}
