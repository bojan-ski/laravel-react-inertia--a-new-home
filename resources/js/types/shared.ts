import type { AdminNavTranslations } from '@/types/admin'
import type { FooterTranslations, NavTranslations } from '@/types/navigation'

export interface SharedProps {
    name: string
    nav: NavTranslations
    footer: FooterTranslations
    adminNav: AdminNavTranslations
    auth: {
        user: AuthUser | null
    }
    flash: {
        success: string | null
        error: string | null
    }
    [key: string]: unknown
}

export type UserRole = 'regular_user' | 'agency' | 'admin'

export interface AuthUser {
    id: number
    name: string
    email: string
    role: UserRole
}
