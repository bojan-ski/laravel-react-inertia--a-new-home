import { usePage } from '@inertiajs/react'
import type { PublicLayoutProps } from '@/types/layout'
import type { SharedProps } from '@/types/shared'

export default function PublicLayout({ children }: PublicLayoutProps) {
    const { name } = usePage<SharedProps>().props

    return (
        <div className="flex min-h-screen flex-col bg-background text-text">
            <header className="border-b border-border bg-surface">
                <div className="mx-auto flex h-16 max-w-7xl items-center px-6">
                    <span className="font-display text-xl font-bold text-text">{name}</span>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="bg-surface-dark text-text-inverse">
                <div className="mx-auto max-w-7xl px-6 py-10 text-sm text-text-muted">{name}</div>
            </footer>
        </div>
    )
}
