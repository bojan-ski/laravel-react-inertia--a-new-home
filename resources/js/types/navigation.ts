import type { ReactNode } from 'react'
import type { AuthUser, UserRole } from '@/types/shared'

export const dashboardPaths: Record<UserRole, string> = {
    regular_user: '/profile',
    agency: '/agency',
    admin: '/admin',
}

export interface NavTranslations {
    brand: string
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
    aria_label: string
    skip_to_content: string
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

export interface NavbarBackBarProps extends NavBack {
    action?: ReactNode
    className?: string
}

export type NavbarVariant = 'default' | 'on-photo'

export type NavTone = 'ink' | 'paper'

export interface NavbarProps {
    back?: NavBack
    backAction?: ReactNode
    variant?: NavbarVariant
    ruled?: boolean
}

export interface NavbarLinkProps {
    item: NavItem
    active: boolean
    className: string
    tone?: NavTone
    onNavigate?: () => void
}

export interface NavbarAuthActionsProps {
    user: AuthUser | null
    variant: 'desktop' | 'panel'
    tone?: NavTone
    onNavigate?: () => void
}

export interface NavbarMobilePanelProps {
    id: string
    label: string
    items: NavItem[]
    currentPath: string
    user: AuthUser | null
    onClose: () => void
}

export interface AccountTabsTranslations {
    label: string
    my_listings: string
    agency_profile: string
    favorites: string
    saved_searches: string
    messages: string
    account_settings: string
}

export interface AccountTab {
    label: string
    href: string
}

export interface AccountPageHeaderProps {
    title: string
    subtitle: string
    leading?: ReactNode
    action?: ReactNode
    mobileAction?: ReactNode
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
    aria_label: string
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
