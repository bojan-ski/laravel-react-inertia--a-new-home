import type { AdminCounts, AdminNavItem, AdminNavTranslations } from '@/types/admin'

export function buildAdminNavItems(nav: AdminNavTranslations, counts: AdminCounts | null): AdminNavItem[] {
    return [
        { key: 'dashboard', label: nav.dashboard, href: '/admin' },
        { key: 'listings', label: nav.listings, href: '/admin/listings', count: counts?.pendingListings },
        {
            key: 'agencies',
            label: nav.agencies,
            href: '/admin/agencies',
            count: counts ? counts.pendingAgencies + counts.pendingAgencyChanges : undefined,
        },
        { key: 'users', label: nav.users, href: '/admin/users' },
        {
            key: 'reported_listings',
            label: nav.reported_listings,
            href: '/admin/listing-reports',
            count: counts?.reportedListings,
        },
        {
            key: 'reported_messages',
            label: nav.reported_messages,
            href: '/admin/reported-messages',
            count: counts?.reportedMessages,
        },
        {
            key: 'contact_messages',
            label: nav.contact_messages,
            href: '/admin/contact-messages',
            count: counts?.newContactMessages,
        },
    ]
}

export function isAdminNavItemActive(href: string, url: string): boolean {
    const path = url.split(/[?#]/)[0]

    if (href === '/admin') {
        return path === '/admin'
    }

    return path === href || path.startsWith(`${href}/`)
}
