import { Link, usePage } from '@inertiajs/react'
import MessagesNavLink from '@/Components/Conversations/MessagesNavLink'
import type { SharedProps } from '@/types/shared'
import { dashboardPaths, type NavbarAuthActionsProps } from '@/types/navigation'

export default function NavbarAuthActions({ onNavigate }: NavbarAuthActionsProps) {
    const { auth, nav } = usePage<SharedProps>().props
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
                    className="rounded-full bg-brand px-5 py-2 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                >
                    {nav.get_started}
                </Link>
            </>
        )
    }

    const dashboardPath = dashboardPaths[user.role] ?? '/profile'

    return (
        <>
            <span className="text-sm font-medium text-text md:max-xl:hidden">{user.name}</span>

            <MessagesNavLink label={nav.messages} unreadLabel={nav.unread} onNavigate={onNavigate} />

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
