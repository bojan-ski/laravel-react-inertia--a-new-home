import type { BadgeStatusValue, StatusBadgeProps } from '@/types/ui'

const statusStyles: Record<BadgeStatusValue, string> = {
    pending_review: 'bg-status-pending-review/10 text-status-pending-review',
    approved: 'bg-status-approved/10 text-status-approved',
    rejected: 'bg-status-rejected/10 text-status-rejected',
    hidden: 'bg-status-hidden/10 text-status-hidden',
    sold: 'bg-status-sold/10 text-status-sold',
    rented: 'bg-status-rented/10 text-status-rented',
    active: 'bg-status-approved/10 text-status-approved',
    suspended: 'bg-status-suspended/10 text-status-suspended',
}

export default function StatusBadge({ status, label }: StatusBadgeProps) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium uppercase tracking-wide ${statusStyles[status]}`}
        >
            {label}
        </span>
    )
}
