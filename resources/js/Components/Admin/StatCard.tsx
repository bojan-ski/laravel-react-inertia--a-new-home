import type { StatCardProps } from '@/types/admin'

export default function StatCard({ label, value, description }: StatCardProps) {
    return (
        <div className="rounded-2xl border border-border bg-surface p-6">
            <p className="text-xs font-medium uppercase tracking-wider text-text-muted">{label}</p>
            <p className="mt-3 font-display text-4xl font-semibold text-text">{value}</p>
            {description && <p className="mt-2 text-sm text-text-soft">{description}</p>}
        </div>
    )
}
