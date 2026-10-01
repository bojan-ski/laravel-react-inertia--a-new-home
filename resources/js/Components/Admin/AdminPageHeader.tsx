import type { AdminPageHeaderProps } from '@/types/admin'

export default function AdminPageHeader({ title, subtitle }: AdminPageHeaderProps) {
    return (
        <header className="flex flex-col gap-2 lg:gap-2.5">
            <h1 className="text-[44px] leading-none font-extralight tracking-[-0.04em] wrap-break-word lg:text-[72px] lg:leading-[0.95] lg:tracking-[-0.045em]">
                {title}
            </h1>
            {subtitle && <p className="text-sm text-ink-muted lg:text-base lg:font-book">{subtitle}</p>}
        </header>
    )
}
