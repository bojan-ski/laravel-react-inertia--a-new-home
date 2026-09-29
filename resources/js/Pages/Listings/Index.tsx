import { Head } from '@inertiajs/react'
import ListingCard from '@/Components/Listings/ListingCard'
import ListingFilterBar from '@/Components/Listings/ListingFilterBar'
import ListingsEmptyState from '@/Components/Listings/ListingsEmptyState'
import Pagination from '@/Components/UI/Pagination'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { ListingsIndexPageProps } from '@/types/listing'

export default function Index({ listings, filters, translations }: ListingsIndexPageProps) {
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
        <ListingsLayout>
            <Head title={translations.title} />

            <ListingFilterBar
                filters={filters}
                translations={translations.filters}
                amenityLabels={translations.amenities}
            />

            <div className="mx-auto max-w-7xl px-6 py-8">
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
        </ListingsLayout>
    )
}
