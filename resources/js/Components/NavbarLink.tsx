import { Link, usePage } from '@inertiajs/react'
import MessagesNavLink from '@/Components/Conversations/MessagesNavLink'
import type { NavbarLinkProps } from '@/types/navigation'
import type { SharedProps } from '@/types/shared'

export default function NavbarLink({ item, active, className, onNavigate }: NavbarLinkProps) {
    const { nav } = usePage<SharedProps>().props
    const classes = `flex items-center transition-colors duration-150 hover:text-forest ${
        active ? 'text-forest' : 'text-ink'
    } ${className}`

    if (item.key === 'messages') {
        return (
            <MessagesNavLink
                label={item.label}
                unreadLabel={nav.unread}
                active={active}
                className={classes}
                onNavigate={onNavigate}
            />
        )
    }

    return (
        <Link href={item.href} onClick={onNavigate} aria-current={active ? 'page' : undefined} className={classes}>
            {item.label}
        </Link>
    )
}
