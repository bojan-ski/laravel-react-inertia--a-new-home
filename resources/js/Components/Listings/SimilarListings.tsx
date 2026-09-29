import { Deferred } from '@inertiajs/react'
import ListingCard from '@/Components/Listings/ListingCard'
import type { ListingCardData, ListingsCardTranslations, SimilarListingsTranslations } from '@/types/listing'

const SKELETON_CARDS = 4

const gridClass = 'mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4'

export interface SimilarListingsProps {
    listings: ListingCardData[] | undefined
    translations: SimilarListingsTranslations
    cardTranslations: ListingsCardTranslations
    propertyTypeLabels: Record<string, string>
}

export default function SimilarListings({
    listings,
    translations,
    cardTranslations,
    propertyTypeLabels,
}: SimilarListingsProps) {
    const heading = <h2 className="font-display text-2xl font-semibold text-text">{translations.heading}</h2>

    const skeleton = (
        <section className="mt-16" aria-busy="true">
            {heading}
            <p className="sr-only">{translations.loading}</p>
            <div className={gridClass}>
                {Array.from({ length: SKELETON_CARDS }, (_, index) => (
                    <div
                        key={index}
                        className="flex animate-pulse flex-col overflow-hidden rounded-xl border border-border bg-surface"
                    >
                        <div className="aspect-4/3 bg-surface-muted" />
                        <div className="flex flex-col gap-3 p-4">
                            <div className="h-3 w-1/2 rounded bg-surface-muted" />
                            <div className="h-4 w-3/4 rounded bg-surface-muted" />
                            <div className="h-3 w-1/3 rounded bg-surface-muted" />
                            <div className="mt-2 h-5 w-2/5 rounded bg-surface-muted" />
                        </div>
                    </div>
                ))}
            </div>
        </section>
    )

    return (
        <Deferred data="similarListings" fallback={skeleton}>
            {listings && listings.length > 0 ? (
                <section className="mt-16">
                    {heading}
                    <div className={gridClass}>
                        {listings.map((listing) => (
                            <ListingCard
                                key={listing.id}
                                listing={listing}
                                translations={cardTranslations}
                                propertyTypeLabels={propertyTypeLabels}
                            />
                        ))}
                    </div>
                </section>
            ) : null}
        </Deferred>
    )
}
