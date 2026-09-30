import type { AdminNavTranslations } from '@/types/admin'
import type { FooterTranslations, NavTranslations } from '@/types/navigation'

export interface SharedProps {
    name: string
    nav: NavTranslations
    ui: UiTranslations
    footer: FooterTranslations
    adminNav: AdminNavTranslations
    auth: {
        user: AuthUser | null
    }
    flash: {
        success: string | null
        error: string | null
        throttled: string | null
    }
    unreadMessages: number
    messageNotifications: MessageNotificationTranslations | null
    cookieConsent: CookieConsent | null
    cookieBanner: CookieBannerTranslations
    [key: string]: unknown
}

export type CookieConsent = 'accepted' | 'rejected'

export interface UiTranslations {
    dismiss: string
    show_password: string
    hide_password: string
}

export interface MessageNotificationTranslations {
    title: string
    view: string
    dismiss: string
}

export interface CookieBannerTranslations {
    title: string
    body: string
    policy_link: string
    accept: string
    reject: string
}

export type UserRole = 'regular_user' | 'agency' | 'admin'

export interface AuthUser {
    id: number
    name: string
    email: string
    role: UserRole
}
