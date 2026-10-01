import { Link } from '@inertiajs/react'
import Kicker from '@/Components/UI/Kicker'
import type { AdminMobileStatsProps, StatCardProps } from '@/types/admin'

function ModerationRow({ label, value, href }: StatCardProps) {
    const classes = 'flex min-h-14 items-center justify-between gap-4 border-t border-rule text-[15px]'
    const content = (
        <>
            <span>{label}</span>
            <span className={`text-[28px] leading-none font-airy ${value > 0 ? 'text-forest' : 'text-ink'}`}>
                {value.toLocaleString('en-GB')}
            </span>
        </>
    )

    return href ? (
        <Link href={href} className={`${classes} transition-colors duration-150 hover:bg-sand/55`}>
            {content}
        </Link>
    ) : (
        <div className={classes}>{content}</div>
    )
}

function GridCell({ label, value, href }: StatCardProps) {
    const classes = 'flex flex-col gap-1 border-t border-rule py-4'
    const content = (
        <>
            <span className="text-[30px] leading-none font-extralight tracking-[-0.03em]">
                {value.toLocaleString('en-GB')}
            </span>
            <span className="text-[13px] text-ink-muted">{label}</span>
        </>
    )

    return href ? (
        <Link href={href} className={`${classes} transition-colors duration-150 hover:bg-sand/55`}>
            {content}
        </Link>
    ) : (
        <div className={classes}>{content}</div>
    )
}

export default function AdminMobileStats({ heading, moderationCards, otherCards }: AdminMobileStatsProps) {
    return (
        <div className="flex flex-col gap-7 md:hidden">
            <section className="flex flex-col">
                <Kicker as="h2" size="sm" className="mb-2">
                    {heading}
                </Kicker>
                <div className="flex flex-col border-b border-rule">
                    {moderationCards.map((card) => (
                        <ModerationRow key={card.label} {...card} />
                    ))}
                </div>
            </section>

            <div className="grid grid-cols-2 gap-x-4">
                {otherCards.map((card) => (
                    <GridCell key={card.label} {...card} />
                ))}
            </div>
        </div>
    )
}
