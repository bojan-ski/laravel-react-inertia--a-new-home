import { Link } from '@inertiajs/react'
import LatestHomeCard from '@/Components/Landing/LatestHomeCard'
import type { LatestHomesProps } from '@/types/home'

export default function LatestHomes({ content, listings }: LatestHomesProps) {
    if (listings.length === 0) {
        return null
    }

    return (
        <section className="mx-auto flex max-w-360 flex-col gap-6 pb-20 pl-5 md:pl-10 lg:gap-10 lg:px-16 lg:pb-40">
            <div className="flex items-baseline justify-between gap-4 pr-5 md:pr-10 lg:pr-0">
                <h2 className="text-[38px] leading-[normal] font-extralight tracking-[-0.03em] lg:text-display-m lg:leading-[normal] lg:tracking-[-0.035em]">
                    {content.heading}
                </h2>
                <Link href="/listings" className="shrink-0 border-b border-current pb-0.5 text-sm lg:pb-1">
                    {content.link}
                </Link>
            </div>

            <div className="flex snap-x snap-mandatory gap-3.5 overflow-x-auto pr-5 md:pr-10 lg:grid lg:grid-cols-3 lg:gap-8 lg:overflow-visible lg:pr-0">
                {listings.map((listing) => (
                    <LatestHomeCard key={listing.id} listing={listing} perMonth={content.per_month} />
                ))}
            </div>
        </section>
    )
}
