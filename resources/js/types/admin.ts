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
