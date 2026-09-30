import { Link, usePage } from '@inertiajs/react'
import MessagesNavLink from '@/Components/Conversations/MessagesNavLink'
import type { NavbarLinkProps } from '@/types/navigation'
import type { SharedProps } from '@/types/shared'

const toneClasses = {
    ink: { base: 'hover:text-forest', active: 'text-forest', idle: 'text-ink' },
    paper: { base: 'hover:text-forest-200 focus-visible:outline-paper', active: 'text-paper', idle: 'text-paper' },
}

export default function NavbarLink({ item, active, className, tone = 'ink', onNavigate }: NavbarLinkProps) {
    const { nav } = usePage<SharedProps>().props
    const palette = toneClasses[tone]
    const classes = `flex items-center transition-colors duration-150 ${palette.base} ${
        active ? palette.active : palette.idle
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
