import { Head, usePage } from '@inertiajs/react'
import ListingCard from '@/Components/Listings/ListingCard'
import ListingFilterBar from '@/Components/Listings/ListingFilterBar'
import ListingsEmptyState from '@/Components/Listings/ListingsEmptyState'
import SaveSearchButton from '@/Components/SavedSearches/SaveSearchButton'
import FlashBanner from '@/Components/UI/FlashBanner'
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
    const { flash } = usePage<SharedProps>().props
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

            <div className="mx-auto max-w-7xl px-6 py-8">
                {flash.success && <FlashBanner message={flash.success} variant="success" />}
                {flash.error && <FlashBanner message={flash.error} variant="error" />}

                <p className="text-sm text-text-muted">
                    {translations.count_prefix} <strong className="text-text">{listings.total}</strong>{' '}
                    {translations.count_suffix}
                </p>

                {listings.data.length === 0 ? (
                    <ListingsEmptyState translations={translations.empty} hasActiveFilters={hasActiveFilters} />
                ) : (
                    <>
                        <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {listings.data.map((listing) => (
                                <ListingCard
                                    key={listing.id}
                                    listing={listing}
                                    translations={translations.card}
                                    propertyTypeLabels={translations.filters.property_types}
                                />
                            ))}
                        </div>

                        <Pagination paginator={listings} labels={translations.pagination} />
                    </>
                )}
            </div>
        </PublicLayout>
    )
}
