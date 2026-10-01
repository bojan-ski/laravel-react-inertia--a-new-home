import { Link } from '@inertiajs/react'
import type { StatCardProps } from '@/types/admin'

export default function StatCard({ label, value, description, attention = false, href }: StatCardProps) {
    const highlighted = attention && value > 0
    const classes = `flex flex-col gap-1.5 border-t pt-6 pr-6 pb-7 ${highlighted ? 'border-forest' : 'border-rule'}`

    const content = (
        <>
            <span
                className={`text-5xl leading-none font-extralight tracking-[-0.035em] xl:text-[56px] ${
                    highlighted ? 'text-forest' : 'text-ink'
                }`}
            >
                {value.toLocaleString('en-GB')}
            </span>
            <span className="text-sm font-medium">{label}</span>
            {description && <span className="text-[13px] text-ink-muted">{description}</span>}
        </>
    )

    if (href) {
        return (
            <Link href={href} className={`${classes} transition-colors duration-150 hover:bg-sand/55`}>
                {content}
            </Link>
        )
    }

    return <div className={classes}>{content}</div>
}
