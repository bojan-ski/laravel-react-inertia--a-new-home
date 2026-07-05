import type { ReportMessageTranslations } from '@/types/reported-message'

export interface ConversationListingSummary {
    id: number
    title: string
    slug: string
}

export interface ConversationParticipant {
    id: number
    name: string
}

export interface ChatMessageData {
    id: number
    senderId: number
    senderName: string
    body: string
    createdAt: string
}

export interface ConversationInboxItem {
    id: number
    listing: ConversationListingSummary
    otherParticipant: ConversationParticipant
    lastMessage: {
        body: string
        createdAt: string
    } | null
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
}

export interface ConversationsTranslations {
    title: string
    subtitle: string
    view_listing: string
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
    translations: ConversationsTranslations
    [key: string]: unknown
}

export interface ConversationRowProps {
    conversation: ConversationInboxItem
    translations: ConversationsTranslations
}

export interface MessageBubbleProps {
    message: ChatMessageData
    isOwn: boolean
    showName: boolean
    reportLabel?: string
    onReport?: () => void
}

export interface MessageComposerProps {
    conversationId: number
    translations: ConversationsThreadTranslations
}
