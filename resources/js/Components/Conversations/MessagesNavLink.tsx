import { Link, usePage } from '@inertiajs/react'
import type { MessagesNavLinkProps } from '@/types/conversation'
import type { SharedProps } from '@/types/shared'

export default function MessagesNavLink({ label, unreadLabel, active, className, onNavigate }: MessagesNavLinkProps) {
    const { unreadMessages } = usePage<SharedProps>().props
    const count = unreadMessages ?? 0

    return (
        <Link
            href="/conversations"
            onClick={onNavigate}
            aria-current={active ? 'page' : undefined}
            className={`gap-2 ${className}`}
        >
            <span>{label}</span>
            {count > 0 && (
                <span
                    className="bg-forest px-1.75 py-0.5 text-[11px] leading-none tracking-normal text-paper"
                    aria-label={unreadLabel.replace(':count', String(count))}
                >
                    {count > 99 ? '99+' : count}
                </span>
            )}
        </Link>
    )
}
