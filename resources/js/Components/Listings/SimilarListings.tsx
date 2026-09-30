import { Deferred, Link } from '@inertiajs/react'
import ListingTeaserCard from '@/Components/Listings/ListingTeaserCard'
import type { ListingCardData, SimilarListingsTranslations } from '@/types/listing'

const SKELETON_CARDS = 3

const scrollerClass =
    '-mr-5 flex snap-x snap-mandatory gap-3.5 overflow-x-auto pr-5 md:-mr-10 md:pr-10 lg:mr-0 lg:grid lg:grid-cols-3 lg:gap-8 lg:overflow-visible lg:pr-0'

export interface SimilarListingsProps {
    listings: ListingCardData[] | undefined
    translations: SimilarListingsTranslations
    perMonth: string
    browseHref: string
}

export default function SimilarListings({ listings, translations, perMonth, browseHref }: SimilarListingsProps) {
    const header = (
        <div className="flex items-baseline justify-between gap-4 border-t border-rule pt-10 lg:pt-16">
            <h2 className="text-[32px] leading-[normal] font-extralight tracking-[-0.03em] lg:text-[56px] lg:tracking-[-0.035em]">
                {translations.heading}
            </h2>
            <Link href={browseHref} className="hidden shrink-0 border-b border-current pb-1 text-sm lg:inline-block">
                {translations.browse}
            </Link>
        </div>
    )

    const skeleton = (
        <section className="flex flex-col gap-5 lg:gap-10" aria-busy="true">
            {header}
            <p className="sr-only">{translations.loading}</p>
            <div className={scrollerClass}>
                {Array.from({ length: SKELETON_CARDS }, (_, index) => (
                    <div key={index} className="flex w-62.5 flex-none animate-pulse flex-col gap-3 lg:w-auto lg:gap-4">
                        <div className="aspect-4/3 bg-sand" />
                        <div className="h-4 w-3/4 bg-sand" />
                        <div className="h-3.5 w-1/2 bg-sand" />
                    </div>
                ))}
            </div>
        </section>
    )

    return (
        <Deferred data="similarListings" fallback={skeleton}>
            {listings && listings.length > 0 ? (
                <section className="flex flex-col gap-5 lg:gap-10">
                    {header}
                    <div className={scrollerClass}>
                        {listings.map((listing) => (
                            <ListingTeaserCard
                                key={listing.id}
                                listing={listing}
                                perMonth={perMonth}
                                aspect="4/3"
                                mobileWidth="w-62.5"
                            />
                        ))}
                    </div>
                </section>
            ) : null}
        </Deferred>
    )
}
