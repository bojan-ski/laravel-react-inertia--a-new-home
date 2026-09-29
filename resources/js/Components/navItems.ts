import { dashboardPaths, type NavItem, type NavTranslations } from '@/types/navigation'
import type { AuthUser } from '@/types/shared'

export function buildNavItems(nav: NavTranslations, user: AuthUser | null): NavItem[] {
    const items: NavItem[] = [
        { key: 'listings', label: nav.listings, href: '/listings', activePaths: ['/listings'] },
        { key: 'agencies', label: nav.agencies, href: '/agencies', activePaths: ['/agencies'] },
    ]

    if (!user) {
        return [...items, { key: 'login', label: nav.login, href: '/login', activePaths: [] }]
    }

    if (user.role !== 'admin') {
        items.push({
            key: 'favorites',
            label: nav.favorites,
            href: '/bookmarks',
            activePaths: ['/bookmarks', '/saved-searches'],
        })
    }

    return [
        ...items,
        { key: 'messages', label: nav.messages, href: '/conversations', activePaths: ['/conversations'] },
        {
            key: 'dashboard',
            label: nav.dashboard,
            href: dashboardPaths[user.role] ?? '/profile',
            activePaths: ['/profile', '/my-listings', '/agency', '/admin'],
        },
    ]
}

export function isNavItemActive(item: NavItem, url: string): boolean {
    const path = url.split(/[?#]/)[0]

    return item.activePaths.some((activePath) => path === activePath || path.startsWith(`${activePath}/`))
}
