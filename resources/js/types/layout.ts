import type { ReactNode } from 'react'
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
}

export interface AuthLayoutProps {
    children: ReactNode
}

export interface AdminLayoutProps {
    children: ReactNode
}

export interface SectionDividerProps {
    className?: string
}
