import type { AgencyBusinessDetails } from '@/types/agency'
import type { ListingShowData, ListingsShowTranslations } from '@/types/listing'
import type {
    AdminListingReportsTranslations,
    ListingReportModerationItem,
    ListingReportReasonValue,
} from '@/types/listing-report'
import type { ConfirmActionTranslations, ModerationFlashTranslations, RejectDialogTranslations } from '@/types/moderation'
import type { Paginator, PaginationLabels } from '@/types/pagination'

export interface AdminStats {
    totalListings: number
    pendingListings: number
    approvedListings: number
    rejectedListings: number
    closedListings: number
    totalRegularUsers: number
    totalAgencyUsers: number
    totalAdmins: number
    pendingAgencies: number
    approvedAgencies: number
    pendingAgencyChanges: number
    reportedListings: number
    reportedMessages: number
    newContactMessages: number
}

export interface AdminNavTranslations {
    eyebrow: string
    dashboard: string
    listings: string
    agencies: string
    users: string
    reported_listings: string
    reported_messages: string
    contact_messages: string
    view_site: string
    aria_label: string
    pending_count: string
}

export interface AdminCounts {
    pendingListings: number
    pendingAgencies: number
    pendingAgencyChanges: number
    reportedListings: number
    reportedMessages: number
    newContactMessages: number
}

export interface AdminNavItem {
    key: string
    label: string
    href: string
    count?: number
}

export interface AdminNavLinkProps {
    item: AdminNavItem
    active: boolean
    pendingTemplate: string
    className?: string
    onNavigate?: () => void
}

export interface AdminMobileMenuProps {
    id: string
    items: AdminNavItem[]
    currentPath: string
    nav: AdminNavTranslations
    signOutLabel: string
    onClose: () => void
}

export interface AdminPageHeaderProps {
    title: string
    subtitle?: string
}

export interface AdminStatLabel {
    label: string
    description?: string
}

export interface AdminDashboardTranslations {
    title: string
    subtitle: string
    sections: {
        listings: string
        users: string
        moderation: string
    }
    stats: {
        total_listings: AdminStatLabel
        pending_listings: AdminStatLabel
        approved_listings: AdminStatLabel
        rejected_listings: AdminStatLabel
        closed_listings: AdminStatLabel
        regular_users: AdminStatLabel
        agency_users: AdminStatLabel
        admins: AdminStatLabel
        pending_agencies: AdminStatLabel
        agency_changes: AdminStatLabel
        reported_listings: AdminStatLabel
        reported_messages: AdminStatLabel
        contact_messages: AdminStatLabel
    }
}

export interface AdminDashboardPageProps {
    stats: AdminStats
    translations: AdminDashboardTranslations
}

export interface StatCardProps {
    label: string
    value: number
    description?: string
    attention?: boolean
    href?: string
}

export interface AdminStatSectionProps {
    heading: string
    cards: StatCardProps[]
}

export interface AdminMobileStatsProps {
    heading: string
    moderationCards: StatCardProps[]
    otherCards: StatCardProps[]
}

export type UserStatusValue = 'active' | 'suspended'

export type AdminUserRole = 'regular_user' | 'agency'

export interface AdminUserAgency {
    id: number
    name: string
    status: string
}

export interface AdminUserCan {
    canSuspend: boolean
    canReactivate: boolean
    canDelete: boolean
}

export interface AdminUserListItem {
    id: number
    name: string
    email: string
    role: AdminUserRole
    status: UserStatusValue
    suspendedAt: string | null
    createdAt: string
    agency: AdminUserAgency | null
    can: AdminUserCan
}

export interface AdminUsersFilters {
    role: string | null
    status: string | null
    search: string | null
}

export interface AdminUsersTranslations {
    title: string
    subtitle: string
    search_placeholder: string
    filters: {
        all_roles: string
        all_statuses: string
        reset: string
        search: string
    }
    roles: Record<AdminUserRole, string>
    statuses: Record<UserStatusValue, string>
    count: string
    joined: string
    suspended_on: string
    agency_line: string
    actions: {
        suspend: string
        reactivate: string
        delete: string
    }
    confirm: ConfirmActionTranslations
    empty: {
        title: string
        filtered: string
        reset: string
    }
    flash: {
        suspended: string
        reactivated: string
        deleted: string
    }
    pagination: PaginationLabels
}

export interface AdminUsersPageProps {
    users: Paginator<AdminUserListItem>
    filters: AdminUsersFilters
    translations: AdminUsersTranslations
}

export interface AdminUserFilterForm {
    role: string
    status: string
    search: string
}

export interface AdminUserFilterBarProps {
    filters: AdminUsersFilters
    translations: AdminUsersTranslations
}

export interface AdminUserRowProps {
    user: AdminUserListItem
    translations: AdminUsersTranslations
}

export interface ConfirmDeleteUserDialogProps {
    open: boolean
    onClose: () => void
    userId: number
    userName: string
    userEmail: string
    translations: AdminUsersTranslations
}

export type ListingTypeValue = 'sale' | 'rent'

export interface AdminListingModerationItem {
    id: number
    title: string
    slug: string
    listingType: ListingTypeValue
    propertyType: string
    price: string
    currency: string
    city: string
    country: string
    ownerName: string | null
    ownerEmail: string | null
    createdAt: string | null
    imageUrl: string | null
}

export interface AdminListingsTranslations {
    title: string
    subtitle: string
    listing_types: Record<ListingTypeValue, string>
    per_month: string
    owner: string
    submitted: string
    actions: {
        approve: string
        reject: string
    }
    dialog: RejectDialogTranslations
    empty: {
        title: string
        subtitle: string
    }
    flash: ModerationFlashTranslations
    pagination: PaginationLabels
    view_listing: string
    review: AdminListingReviewTranslations
}

export type AdminListingOwnerType = 'user' | 'agency'

export type AdminListingStatusValue = 'pending_review' | 'approved' | 'rejected' | 'hidden' | 'sold' | 'rented'

export interface AdminListingReviewTranslations {
    back: string
    moderation_heading: string
    owner_label: string
    submitted_label: string
    expires_label: string
    rejection_reason_label: string
    owner_types: Record<AdminListingOwnerType, string>
    statuses: Record<AdminListingStatusValue, string>
}

export interface AdminListingReviewData extends ListingShowData {
    status: AdminListingStatusValue
    rejectionReason: string | null
    ownerType: AdminListingOwnerType
    ownerName: string | null
    ownerEmail: string | null
    createdAt: string | null
    expiresAt: string | null
}

export interface AdminListingReviewPageProps {
    listing: AdminListingReviewData
    canModerate: boolean
    translations: AdminListingsTranslations
    listingTranslations: ListingsShowTranslations
    propertyTypeLabels: Record<string, string>
    amenityLabels: Record<string, string>
    reportModeration: ListingReportModerationItem | null
    reportTranslations: AdminListingReportsTranslations
    reasonLabels: Record<ListingReportReasonValue, string>
}

export interface ListingModerationActionsProps {
    listingId: number
    listingTitle: string
    translations: AdminListingsTranslations
    layout?: 'inline' | 'stacked'
}

export interface ListingModerationPanelProps {
    listing: AdminListingReviewData
    canModerate: boolean
    translations: AdminListingsTranslations
    className?: string
}

export interface ListingReportPanelProps {
    report: ListingReportModerationItem
    translations: AdminListingReportsTranslations
    reasonLabels: Record<ListingReportReasonValue, string>
}

export interface AdminListingsPageProps {
    listings: Paginator<AdminListingModerationItem>
    translations: AdminListingsTranslations
    propertyTypeLabels: Record<string, string>
}

export interface AdminListingModerationRowProps {
    listing: AdminListingModerationItem
    translations: AdminListingsTranslations
    propertyTypeLabels: Record<string, string>
}

export interface RejectListingDialogProps {
    open: boolean
    onClose: () => void
    listingId: number
    listingTitle: string
    translations: AdminListingsTranslations
}

export interface AdminAgencyModerationItem extends AgencyBusinessDetails {
    id: number
    name: string
    slug: string
    email: string | null
    phone: string | null
    website: string | null
    address: string | null
    description: string | null
    ownerName: string | null
    ownerEmail: string | null
    createdAt: string | null
}

export interface AdminAgenciesTranslations {
    title: string
    subtitle: string
    owner: string
    contact: string
    website: string
    address: string
    legal: string
    registration: string
    registration_value: string
    broker_register: string
    not_provided: string
    submitted: string
    verification_heading: string
    changes: AdminAgencyChangesTranslations
    actions: {
        approve: string
        reject: string
    }
    dialog: RejectDialogTranslations
    empty: {
        title: string
        subtitle: string
    }
    flash: ModerationFlashTranslations
    pagination: PaginationLabels
}

export interface AdminAgencyChangesTranslations {
    heading: string
    subtitle: string
    submitted: string
    field: string
    current: string
    proposed: string
    empty_value: string
    view_public: string
    actions: {
        approve: string
        reject: string
    }
    dialog: RejectDialogTranslations
}

export interface AgencyProfileChangeDiff {
    field: string
    current: string | null
    proposed: string | null
}

export interface AgencyProfileChangeItem {
    agencyId: number
    agencyName: string | null
    agencySlug: string | null
    ownerName: string | null
    ownerEmail: string | null
    submittedAt: string
    changes: AgencyProfileChangeDiff[]
}

export interface AdminAgenciesPageProps {
    agencies: Paginator<AdminAgencyModerationItem>
    profileChanges: AgencyProfileChangeItem[]
    translations: AdminAgenciesTranslations
    fieldLabels: Record<string, string>
}

export interface AdminAgencyModerationRowProps {
    agency: AdminAgencyModerationItem
    translations: AdminAgenciesTranslations
}

export interface AgencyChangeRequestRowProps {
    change: AgencyProfileChangeItem
    translations: AdminAgencyChangesTranslations
    ownerTemplate: string
    fieldLabels: Record<string, string>
}

export interface RejectAgencyDialogProps {
    open: boolean
    onClose: () => void
    actionUrl: string
    subjectName: string
    dialog: RejectDialogTranslations
}
