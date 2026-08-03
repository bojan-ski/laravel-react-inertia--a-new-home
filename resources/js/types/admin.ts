import type { ConfirmActionTranslations, ModerationFlashTranslations, RejectDialogTranslations } from '@/types/moderation'
import type { Paginator, PaginationLabels } from '@/types/pagination'

export interface AdminStats {
    totalListings: number
    pendingListings: number
    approvedListings: number
    rejectedListings: number
    totalRegularUsers: number
    totalAgencyUsers: number
    totalAdmins: number
    pendingAgencies: number
    approvedAgencies: number
    reportedMessages: number
}

export interface AdminNavTranslations {
    eyebrow: string
    dashboard: string
    listings: string
    agencies: string
    users: string
    reported_messages: string
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
        regular_users: AdminStatLabel
        agency_users: AdminStatLabel
        admins: AdminStatLabel
        pending_agencies: AdminStatLabel
        reported_messages: AdminStatLabel
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
}

export interface AdminStatSectionProps {
    heading: string
    cards: StatCardProps[]
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

export interface AdminAgencyModerationItem {
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
}

export interface AdminAgenciesPageProps {
    agencies: Paginator<AdminAgencyModerationItem>
    translations: AdminAgenciesTranslations
}

export interface AdminAgencyModerationRowProps {
    agency: AdminAgencyModerationItem
    translations: AdminAgenciesTranslations
}

export interface RejectAgencyDialogProps {
    open: boolean
    onClose: () => void
    agencyId: number
    agencyName: string
    translations: AdminAgenciesTranslations
}
