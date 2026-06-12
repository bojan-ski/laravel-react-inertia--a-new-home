import { Link, usePage } from '@inertiajs/react'
import type { AuthLayoutProps } from '@/types/layout'
import type { SharedProps } from '@/types/shared'

export default function AuthLayout({ children }: AuthLayoutProps) {
    const { nav } = usePage<SharedProps>().props

    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-background px-6 py-16">
            <Link href="/" className="mb-8 font-display text-3xl font-semibold text-text">
                {nav.brand}
            </Link>

            <div className="w-full max-w-md rounded-xl border border-border bg-surface p-8">
                {children}
            </div>
        </div>
    )
}
