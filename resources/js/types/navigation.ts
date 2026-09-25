import type { UserRole } from '@/types/shared'

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
    login: string
    get_started: string
    dashboard: string
    sign_out: string
    open_menu: string
    close_menu: string
}

export interface NavLink {
    label: string
    href: string
    internal: boolean
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

export interface NavbarAuthActionsProps {
    onNavigate?: () => void
}

export interface NavbarMobilePanelProps {
    links: NavLink[]
    currentPath: string
    onClose: () => void
}
