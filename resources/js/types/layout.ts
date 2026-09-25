import type { ReactNode } from 'react'

export interface AppShellProps {
    children: ReactNode
}

export interface PublicLayoutProps {
    children: ReactNode
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
