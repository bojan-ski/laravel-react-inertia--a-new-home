import { Link } from '@inertiajs/react'
import type { ListingFormHeaderProps } from '@/types/listing'

export default function ListingFormHeader({ title, subtitle, back, backHref }: ListingFormHeaderProps) {
    return (
        <header className="flex flex-col gap-7 px-5 pt-6 md:px-10 lg:pb-10 xl:px-16">
            <Link
                href={backHref}
                className="hidden self-start text-sm text-ink-muted transition-colors duration-150 hover:text-forest lg:inline-flex"
            >
                <span aria-hidden="true">←&nbsp;</span>
                {back}
            </Link>

            <div className="flex flex-col gap-2.5 lg:gap-3">
                <h1 className="text-[48px] leading-[0.95] font-extralight tracking-[-0.045em] wrap-break-word lg:text-display-l">
                    {title}
                </h1>
                <p className="text-[15px] font-book text-ink-muted lg:text-[17px]">{subtitle}</p>
            </div>
        </header>
    )
}
