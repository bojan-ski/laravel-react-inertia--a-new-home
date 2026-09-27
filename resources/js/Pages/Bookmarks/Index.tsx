import { Head, Link } from '@inertiajs/react'
import { Heart } from 'lucide-react'
import ListingCard from '@/Components/Listings/ListingCard'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { BookmarksIndexPageProps } from '@/types/bookmark'

export default function Index({ listings, translations, listingTranslations }: BookmarksIndexPageProps) {
    const countNoun = listings.length === 1 ? translations.count_one : translations.count_other

    return (
        <ListingsLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto max-w-7xl px-6 py-10">
                    <h1 className="font-display text-3xl font-semibold text-text">{translations.title}</h1>
                    <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                </div>
            </div>

            <div className="mx-auto max-w-7xl px-6 py-8">
                {listings.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 py-24 text-center">
                        <Heart className="size-10 text-text-muted" />
                        <p className="text-lg text-text-soft">{translations.empty.headline}</p>
                        <Link
                            href="/listings"
                            className="rounded-full bg-brand px-6 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                        >
                            {translations.empty.cta}
                        </Link>
                    </div>
                ) : (
                    <>
                        <p className="text-sm text-text-muted">
                            {translations.count_prefix} <strong className="text-text">{listings.length}</strong>{' '}
                            {countNoun}
                        </p>

                        <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {listings.map((listing) => (
                                <ListingCard
                                    key={listing.id}
                                    listing={listing}
                                    translations={listingTranslations.card}
                                    propertyTypeLabels={listingTranslations.filters.property_types}
                                />
                            ))}
                        </div>
                    </>
                )}
            </div>
        </ListingsLayout>
    )
}
