import { Head, usePage } from '@inertiajs/react'
import ListingFilterBar from '@/Components/Listings/ListingFilterBar'
import ListingRow from '@/Components/Listings/ListingRow'
import ListingRowCard from '@/Components/Listings/ListingRowCard'
import ListingsEmptyState from '@/Components/Listings/ListingsEmptyState'
import { countLabel } from '@/Components/Listings/listingFormat'
import SaveSearchButton from '@/Components/SavedSearches/SaveSearchButton'
import Pagination from '@/Components/UI/Pagination'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ListingsIndexPageProps } from '@/types/listing'
import type { SharedProps } from '@/types/shared'

export default function Index({
    listings,
    filters,
    canSaveSearch,
    suggestedSearchName,
    searchSummary,
    saveSearchTranslations,
    translations,
}: ListingsIndexPageProps) {
    const { errors } = usePage<SharedProps>().props
    const errorMessages = Object.values(errors)
    const hasActiveFilters = Boolean(
        filters.search ||
            filters.listingType ||
            filters.propertyType ||
            filters.city ||
            filters.minPrice ||
            filters.maxPrice ||
            filters.minBedrooms ||
            filters.minBathrooms ||
            filters.minArea ||
            filters.maxArea ||
            filters.amenities.length > 0
    )

    return (
        <PublicLayout>
            <Head title={translations.title} />

            <div className="mx-auto flex max-w-360 flex-col gap-5 px-5 pt-8 pb-12 md:px-10 lg:gap-10 lg:px-16 lg:pt-18 lg:pb-18">
                <header className="flex items-end justify-between gap-4">
                    <h1 className="text-[60px] leading-[0.9] font-extralight tracking-[-0.045em] md:text-[88px] xl:text-display-xl xl:leading-[0.9]">
                        {translations.title}
                    </h1>
                    <p className="pb-1 text-[13px] text-ink-muted lg:text-[15px]">
                        <span className="xl:hidden">
                            {countLabel(listings.total, translations.count_short_one, translations.count_short)}
                        </span>
                        <span className="hidden xl:inline">
                            {countLabel(listings.total, translations.count_one, translations.count)}
                        </span>
                    </p>
                </header>

                <div>
                    <ListingFilterBar
                        filters={filters}
                        translations={translations.filters}
                        amenityLabels={translations.amenities}
                        actionsSlot={
                            canSaveSearch &&
                            hasActiveFilters && (
                                <SaveSearchButton
                                    filters={filters}
                                    suggestedName={suggestedSearchName}
                                    summary={searchSummary}
                                    translations={saveSearchTranslations}
                                />
                            )
                        }
                    />

                    {errorMessages.length > 0 && (
                        <div role="alert" className="mt-4 flex flex-col gap-1 text-[13px] text-ink">
                            {errorMessages.map((error) => (
                                <p key={error}>{error}</p>
                            ))}
                        </div>
                    )}

                    {listings.data.length === 0 ? (
                        <ListingsEmptyState translations={translations.empty} hasActiveFilters={hasActiveFilters} />
                    ) : (
                        <>
                            <div className="hidden lg:mt-3 lg:block xl:mt-10">
                                {listings.data.map((listing) => (
                                    <ListingRow
                                        key={listing.id}
                                        listing={listing}
                                        translations={translations.card}
                                        propertyTypeLabels={translations.filters.property_types}
                                    />
                                ))}
                            </div>

                            <div className="grid md:grid-cols-2 md:gap-x-8 lg:hidden">
                                {listings.data.map((listing) => (
                                    <ListingRowCard
                                        key={listing.id}
                                        listing={listing}
                                        translations={translations.card}
                                        propertyTypeLabels={translations.filters.property_types}
                                    />
                                ))}
                            </div>

                            <Pagination paginator={listings} labels={translations.pagination} className="mt-6" />
                        </>
                    )}
                </div>
            </div>
        </PublicLayout>
    )
}
