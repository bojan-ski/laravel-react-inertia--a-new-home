import type { EmptyStateProps } from '@/types/ui'

export default function EmptyState({ title, hint, action, className = '' }: EmptyStateProps) {
    return (
        <div className={`flex flex-col items-start gap-2.5 border border-rule p-10 ${className}`}>
            <p className="text-[30px] leading-tight font-airy tracking-[-0.02em] text-ink">{title}</p>
            {hint && <p className="text-sm text-ink-muted">{hint}</p>}
            {action}
        </div>
    )
}
