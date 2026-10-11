import type { ReactNode } from 'react'
import type { AuthImage, AuthLayoutTranslations } from '@/types/auth'
import type { NavBack, NavbarVariant } from '@/types/navigation'

export interface AppShellProps {
    children: ReactNode
}

export interface PublicLayoutProps {
    children: ReactNode
    back?: NavBack
    backAction?: ReactNode
    footer?: boolean
    navVariant?: NavbarVariant
    fill?: 'always' | 'desktop'
    contained?: boolean
}

export interface AuthLayoutProps {
    children: ReactNode
    translations: AuthLayoutTranslations
    image: AuthImage
}

export interface AdminLayoutProps {
    children: ReactNode
}
