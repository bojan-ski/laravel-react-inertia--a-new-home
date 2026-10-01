import type { BadgeStatusValue, StatusBadgeProps } from '@/types/ui'

const positive = 'bg-forest-50 px-2.25 py-1.25 text-forest-800'
const pending = 'bg-sand px-2.25 py-1.25 text-ink'
const outlined = 'border border-ink px-2 py-1 text-ink'
const closed = 'bg-ink px-2.25 py-1.25 text-paper'

const statusStyles: Record<BadgeStatusValue, string> = {
    approved: positive,
    active: positive,
    handled: positive,
    pending_review: pending,
    pending_verification: pending,
    rejected: outlined,
    suspended: outlined,
    sold: closed,
    rented: closed,
    expired: closed,
    hidden: closed,
}

export default function StatusBadge({ status, label }: StatusBadgeProps) {
    return (
        <span
            className={`inline-flex items-center text-[11px] leading-none tracking-widest whitespace-nowrap uppercase ${statusStyles[status]}`}
        >
            {label}
        </span>
    )
}
