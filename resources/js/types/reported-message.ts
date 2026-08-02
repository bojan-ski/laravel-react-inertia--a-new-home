import type { Paginator, PaginationLabels } from '@/types/pagination'

export interface ReportedMessageModerationItem {
    id: number
    reason: string
    createdAt: string
    listingTitle: string | null
    conversationId: number | null
    messageBody: string | null
    messageSenderName: string | null
    reportedByName: string | null
    offendingUserName: string | null
}

export interface ReportedMessagesTranslations {
    title: string
    subtitle: string
    listing_context: string
    conversation: string
    reported_message: string
    sent_by: string
    reason_label: string
    reported_by: string
    actions: {
        resolve: string
        suspend: string
    }
    confirm: {
        title: string
        description: string
        confirm: string
        cancel: string
    }
    empty: {
        title: string
        subtitle: string
    }
    flash: {
        resolved: string
        suspended: string
    }
    pagination: PaginationLabels
}

export interface AdminReportedMessagesPageProps {
    reports: Paginator<ReportedMessageModerationItem>
    translations: ReportedMessagesTranslations
    [key: string]: unknown
}

export interface ReportedMessageModerationRowProps {
    report: ReportedMessageModerationItem
    translations: ReportedMessagesTranslations
}

export interface ReportMessageTranslations {
    action: string
    title: string
    prompt: string
    placeholder: string
    cancel: string
    submit: string
}

export interface ReportMessageDialogProps {
    open: boolean
    onClose: () => void
    messageId: number
    preview: string
    translations: ReportMessageTranslations
}
