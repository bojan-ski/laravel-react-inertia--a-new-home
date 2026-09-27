import { Link, usePage } from '@inertiajs/react'
import { MessageCircle } from 'lucide-react'
import type { MessagesNavLinkProps } from '@/types/conversation'
import type { SharedProps } from '@/types/shared'

export default function MessagesNavLink({ label, unreadLabel, onNavigate }: MessagesNavLinkProps) {
    const { unreadMessages } = usePage<SharedProps>().props
    const count = unreadMessages ?? 0

    return (
        <Link
            href="/conversations"
            onClick={onNavigate}
            className="inline-flex items-center gap-1.5 text-sm font-medium text-text-soft transition-colors hover:text-text"
        >
            <MessageCircle className="size-4" />
            <span className="md:max-lg:sr-only">{label}</span>
            {count > 0 && (
                <span
                    className="inline-flex min-w-5 items-center justify-center rounded-full bg-status-rejected px-1.5 py-0.5 text-xs font-semibold leading-none text-text-inverse"
                    aria-label={unreadLabel.replace(':count', String(count))}
                >
                    {count > 99 ? '99+' : count}
                </span>
            )}
        </Link>
    )
}
