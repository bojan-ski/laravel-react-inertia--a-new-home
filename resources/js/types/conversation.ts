import type { ClosedListingStatusValue, ListingStatusValue } from '@/types/listing'
import type { ReportMessageTranslations } from '@/types/reported-message'

export interface ConversationListingSummary {
    id: number | null
    title: string
    slug: string | null
    status: ListingStatusValue | null
    imageUrl: string | null
    city: string | null
    price: string | null
    currency: string | null
    listingType: 'sale' | 'rent' | null
    isExpired: boolean
    isAvailable: boolean
}

export type ConversationListingLabel = ClosedListingStatusValue | 'expired'

export interface ConversationParticipant {
    id: number | null
    name: string
}

export interface ChatMessageData {
    id: number
    senderId: number | null
    senderName: string
    body: string
    createdAt: string
}

export interface ConversationInboxItem {
    id: number
    listing: ConversationListingSummary
    otherParticipant: ConversationParticipant
    lastMessage: {
        senderId: number | null
        body: string
        createdAt: string
    } | null
    unreadCount: number
    updatedAt: string
}

export interface ConversationShowData {
    id: number
    listing: ConversationListingSummary
    otherParticipant: ConversationParticipant
    messages: ChatMessageData[]
}

export interface ChatMessageBroadcast {
    id: number
    conversationId: number
    senderId: number
    senderName: string
    body: string
    createdAt: string
}

export interface ChatMessageReceivedBroadcast {
    conversationId: number
    senderName: string
    preview: string
    listingTitle: string | null
}

export interface MessageNotifierProps {
    userId: number
}

export interface MessagesNavLinkProps {
    label: string
    unreadLabel: string
    active: boolean
    className: string
    onNavigate?: () => void
}

export interface ConversationsEmptyTranslations {
    headline: string
    subtitle: string
}

export interface ConversationsThreadTranslations {
    back: string
    with: string
    empty: string
    placeholder: string
    send: string
    sending: string
    hint: string
    read_only: string
}

export interface ConversationsTranslations {
    title: string
    subtitle: string
    view_listing: string
    deleted_user: string
    listing_removed: string
    unread: string
    select_prompt: string
    you_prefix: string
    per_month: string
    today: string
    yesterday: string
    listing_status: Record<ConversationListingLabel, string>
    empty: ConversationsEmptyTranslations
    thread: ConversationsThreadTranslations
    report: ReportMessageTranslations
}

export interface ConversationsIndexPageProps {
    conversations: ConversationInboxItem[]
    translations: ConversationsTranslations
    [key: string]: unknown
}

export interface ConversationShowPageProps {
    conversation: ConversationShowData
    conversations: ConversationInboxItem[]
    canReply: boolean
    translations: ConversationsTranslations
    [key: string]: unknown
}

export interface ConversationInboxProps {
    conversations: ConversationInboxItem[]
    translations: ConversationsTranslations
    selectedId?: number
    titleAs?: 'h1' | 'h2'
    className?: string
}

export interface ConversationRowProps {
    conversation: ConversationInboxItem
    translations: ConversationsTranslations
    currentUserId: number | null
    selected: boolean
}

export interface ConversationThumbnailProps {
    listing: ConversationListingSummary
    className: string
}

export interface ThreadHeaderProps {
    conversation: ConversationShowData
    translations: ConversationsTranslations
}

export interface MessageBubbleProps {
    message: ChatMessageData
    isOwn: boolean
    showName: boolean
    reportLabel?: string
    onReport?: () => void
}

export interface DaySeparatorLabels {
    today: string
    yesterday: string
}

export interface MessageComposerProps {
    conversationId: number
    translations: ConversationsThreadTranslations
}
