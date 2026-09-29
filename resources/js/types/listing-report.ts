import type { RejectDialogTranslations } from '@/types/moderation'
import type { Paginator, PaginationLabels } from '@/types/pagination'

export type ListingReportReasonValue =
    | 'scam'
    | 'incorrect_info'
    | 'no_longer_available'
    | 'duplicate'
    | 'offensive'
    | 'other'

export interface ListingReportTranslations {
    action: string
    dialog: {
        title: string
        reason_label: string
        details_label: string
        details_label_required: string
        details_placeholder: string
        cancel: string
        submit: string
    }
    reasons: Record<ListingReportReasonValue, string>
}

export interface ReportListingButtonProps {
    listingId: number
    translations: ListingReportTranslations
}

export interface ReportListingDialogProps {
    open: boolean
    onClose: () => void
    listingId: number
    translations: ListingReportTranslations
}

export interface ListingReportItem {
    id: number
    reason: ListingReportReasonValue
    details: string | null
    reportedByName: string | null
    createdAt: string | null
}

export interface ListingReportModerationItem {
    id: number
    title: string
    status: string
    imageUrl: string | null
    ownerName: string | null
    ownerEmail: string | null
    openReportsCount: number
    reports: ListingReportItem[]
    canTakeDown: boolean
}

export interface AdminListingReportsTranslations {
    title: string
    subtitle: string
    panel_heading: string
    owner: string
    reports_count_one: string
    reports_count_other: string
    reported_by: string
    not_approved: string
    actions: {
        dismiss: string
        take_down: string
    }
    dialog: RejectDialogTranslations
    empty: {
        title: string
        subtitle: string
    }
    pagination: PaginationLabels
}

export interface AdminListingReportsPageProps {
    listings: Paginator<ListingReportModerationItem>
    translations: AdminListingReportsTranslations
    reasonLabels: Record<ListingReportReasonValue, string>
    [key: string]: unknown
}

export interface ListingReportModerationRowProps {
    listing: ListingReportModerationItem
    translations: AdminListingReportsTranslations
    reasonLabels: Record<ListingReportReasonValue, string>
}

export interface ListingReportListProps {
    reports: ListingReportItem[]
    translations: AdminListingReportsTranslations
    reasonLabels: Record<ListingReportReasonValue, string>
}

export interface ListingReportActionsProps {
    listing: ListingReportModerationItem
    translations: AdminListingReportsTranslations
}
