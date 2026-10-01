import type { ConfirmActionTranslations } from '@/types/moderation'
import type { PaginationLabels, Paginator } from '@/types/pagination'

export interface ContactTranslations {
    eyebrow: string
    title: string
    lead: string
    details_heading: string
    details: string[]
    fields: {
        email: string
        subject: string
        message: string
    }
    placeholders: {
        email: string
        subject: string
        message: string
    }
    submit: string
    sending: string
    flash: string
}

export interface ContactPageProps {
    defaultEmail: string
    translations: ContactTranslations
    [key: string]: unknown
}

export type ContactFormData = {
    email: string
    subject: string
    message: string
    website: string
}

export type ContactMessageStatus = 'new' | 'handled'

export interface ContactMessageItem {
    id: number
    email: string
    subject: string
    message: string
    senderName: string | null
    isHandled: boolean
    handledAt: string | null
    handledByName: string | null
    createdAt: string
}

export interface ContactMessagesEmptyTranslations {
    title: string
    subtitle: string
}

export interface ContactMessagesTranslations {
    title: string
    subtitle: string
    tabs: Record<ContactMessageStatus, string> & {
        new_count: string
        aria_label: string
    }
    from: string
    account: string
    guest: string
    received: string
    handled_by: string
    handled_badge: string
    reply_subject: string
    actions: {
        reply: string
        mark_handled: string
        delete: string
    }
    confirm: ConfirmActionTranslations
    empty: Record<ContactMessageStatus, ContactMessagesEmptyTranslations>
    flash: {
        handled: string
        deleted: string
    }
    pagination: PaginationLabels
}

export interface AdminContactMessagesPageProps {
    messages: Paginator<ContactMessageItem>
    status: ContactMessageStatus
    translations: ContactMessagesTranslations
    [key: string]: unknown
}

export interface ContactMessageRowProps {
    message: ContactMessageItem
    translations: ContactMessagesTranslations
}
