import type { AdminNavTranslations } from '@/types/admin'
import type { ListingsNavTranslations } from '@/types/listing'
import type { FooterTranslations, NavTranslations } from '@/types/navigation'

export interface SharedProps {
    name: string
    nav: NavTranslations
    listingsNav: ListingsNavTranslations
    footer: FooterTranslations
    adminNav: AdminNavTranslations
    auth: {
        user: AuthUser | null
    }
    flash: {
        success: string | null
        error: string | null
    }
    cookieConsent: CookieConsent | null
    cookieBanner: CookieBannerTranslations
    [key: string]: unknown
}

export type CookieConsent = 'accepted' | 'rejected'

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
