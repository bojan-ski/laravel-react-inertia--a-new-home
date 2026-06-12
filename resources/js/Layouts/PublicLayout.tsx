import { usePage } from '@inertiajs/react'
import Navbar from '@/Components/Navbar'
import type { PublicLayoutProps } from '@/types/layout'
import type { SharedProps } from '@/types/shared'

export default function PublicLayout({ children }: PublicLayoutProps) {
    const { name } = usePage<SharedProps>().props

    return (
        <div className="flex min-h-screen flex-col bg-background text-text">
            <Navbar />

            <main className="flex-1">{children}</main>

            <footer className="bg-surface-dark text-text-inverse">
                <div className="mx-auto max-w-7xl px-6 py-10 text-sm text-text-muted">{name}</div>
            </footer>
        </div>
    )
}
