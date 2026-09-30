import type { ListingFilters } from '@/types/listing'

export const BEDROOM_OPTIONS = [1, 2, 3, 4, 5]
export const BATHROOM_OPTIONS = [1, 2, 3, 4]

export const emptyFilters: ListingFilters = {
    search: '',
    listingType: '',
    propertyType: '',
    city: '',
    minPrice: '',
    maxPrice: '',
    minBedrooms: '',
    minBathrooms: '',
    minArea: '',
    maxArea: '',
    amenities: [],
    sort: 'newest',
}

export function nonEmptyFilters(filters: ListingFilters): Record<string, string | string[]> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => (Array.isArray(value) ? value.length > 0 : value !== '')),
    )
}

export function moreFiltersCount(filters: ListingFilters): number {
    return (
        [filters.city, filters.minBathrooms, filters.minArea || filters.maxArea].filter(Boolean).length +
        filters.amenities.length
    )
}

export function sheetFiltersCount(filters: ListingFilters): number {
    return (
        [filters.propertyType, filters.minPrice || filters.maxPrice, filters.minBedrooms].filter(Boolean).length +
        moreFiltersCount(filters)
    )
}
