import { Head, Link } from '@inertiajs/react'
import { Heart, Search } from 'lucide-react'
import ListingCard from '@/Components/Listings/ListingCard'
import PublicLayout from '@/Layouts/PublicLayout'
import type { BookmarksIndexPageProps } from '@/types/bookmark'

export default function Index({
    listings,
    closedListings,
    canManageSavedSearches,
    translations,
    listingTranslations,
}: BookmarksIndexPageProps) {
    const countNoun = listings.length === 1 ? translations.count_one : translations.count_other

    return (
        <PublicLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-10 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="font-display text-3xl font-semibold text-text">{translations.title}</h1>
                        <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                    </div>

                    {canManageSavedSearches && (
                        <Link
                            href="/saved-searches"
                            className="flex items-center gap-1.5 self-start rounded-lg border border-border-strong px-4 py-2.5 text-sm font-medium text-text-soft transition-colors hover:border-text hover:text-text sm:self-auto"
                        >
                            <Search className="size-4" />
                            {translations.saved_searches_link}
                        </Link>
                    )}
                </div>
            </div>

            <div className="mx-auto max-w-7xl px-6 py-8">
                {listings.length === 0 && closedListings.length === 0 ? (
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
                        {listings.length > 0 && (
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

                        {closedListings.length > 0 && (
                            <section className={listings.length > 0 ? 'mt-12 border-t border-border pt-10' : ''}>
                                <h2 className="text-xl font-semibold text-text">{translations.closed.heading}</h2>
                                <p className="mt-1 text-sm text-text-muted">{translations.closed.subtitle}</p>

                                <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                    {closedListings.map((listing) => (
                                        <ListingCard
                                            key={listing.id}
                                            listing={listing}
                                            translations={listingTranslations.card}
                                            propertyTypeLabels={listingTranslations.filters.property_types}
                                            closedLabel={
                                                listing.status === 'approved'
                                                    ? translations.closed.expired
                                                    : translations.closed.status[listing.status]
                                            }
                                        />
                                    ))}
                                </div>
                            </section>
                        )}
                    </>
                )}
            </div>
        </PublicLayout>
    )
}
