import type { AuthUser, UserRole } from '@/types/shared'

export const dashboardPaths: Record<UserRole, string> = {
    regular_user: '/profile',
    agency: '/agency',
    admin: '/admin',
}

export interface NavTranslations {
    brand: string
    features: string
    how_it_works: string
    listings: string
    agencies: string
    favorites: string
    login: string
    get_started: string
    dashboard: string
    sign_out: string
    messages: string
    unread: string
    open_menu: string
    close_menu: string
}

export type NavItemKey = 'listings' | 'agencies' | 'favorites' | 'messages' | 'dashboard' | 'login'

export interface NavItem {
    key: NavItemKey
    label: string
    href: string
    activePaths: string[]
}

export interface NavBack {
    href: string
    label: string
}

export interface NavbarProps {
    back?: NavBack
}

export interface NavbarLinkProps {
    item: NavItem
    active: boolean
    className: string
    onNavigate?: () => void
}

export interface NavbarAuthActionsProps {
    user: AuthUser | null
    variant: 'desktop' | 'panel'
    onNavigate?: () => void
}

export interface NavbarMobilePanelProps {
    id: string
    items: NavItem[]
    currentPath: string
    user: AuthUser | null
    onClose: () => void
}

export interface FooterTranslations {
    brand: string
    tagline: string
    company: string
    legal: string
    connect: string
    about: string
    contact: string
    privacy_policy: string
    terms_of_service: string
    cookie_policy: string
    gdpr: string
    twitter: string
    linkedin: string
    facebook: string
    instagram: string
    copyright: string
    privacy: string
    terms: string
    cookies: string
}

export interface FooterLink {
    label: string
    href: string
    external: boolean
}

export interface FooterLinkColumnProps {
    heading: string
    links: FooterLink[]
}
