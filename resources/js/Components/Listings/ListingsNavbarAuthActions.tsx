import { Link, usePage } from '@inertiajs/react'
import type { SharedProps, UserRole } from '@/types/shared'
import type { ListingsNavbarAuthActionsProps } from '@/types/listing'

const dashboardPaths: Record<UserRole, string> = {
    regular_user: '/profile',
    agency: '/agency',
    admin: '/admin',
}

export default function ListingsNavbarAuthActions({ nav, onNavigate }: ListingsNavbarAuthActionsProps) {
    const { auth } = usePage<SharedProps>().props
    const user = auth?.user ?? null

    if (!user) {
        return (
            <>
                <Link
                    href="/login"
                    onClick={onNavigate}
                    className="text-sm font-medium text-text-soft transition-colors hover:text-text"
                >
                    {nav.login}
                </Link>

                <Link
                    href="/register"
                    onClick={onNavigate}
                    className="rounded-full bg-surface-dark px-5 py-2 text-sm font-semibold text-text-inverse transition-colors hover:bg-text"
                >
                    {nav.sign_up}
                </Link>
            </>
        )
    }

    const dashboardPath = dashboardPaths[user.role] ?? '/profile'

    return (
        <>
            <span className="hidden text-sm font-medium text-text sm:inline">{user.name}</span>

            <Link
                href={dashboardPath}
                onClick={onNavigate}
                className="text-sm font-medium text-text-soft transition-colors hover:text-text"
            >
                {nav.dashboard}
            </Link>

            <Link
                href="/logout"
                method="post"
                as="button"
                onClick={onNavigate}
                className="text-sm font-medium text-text-soft transition-colors hover:text-text"
            >
                {nav.sign_out}
            </Link>
        </>
    )
}
