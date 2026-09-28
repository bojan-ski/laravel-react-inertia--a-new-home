import type { ClosedListingStatusValue, ListingCardData, ListingsTranslations } from '@/types/listing'

export interface BookmarksEmptyTranslations {
    headline: string
    cta: string
}

export interface BookmarksClosedTranslations {
    heading: string
    subtitle: string
    status: Record<ClosedListingStatusValue, string>
}

export interface BookmarksTranslations {
    title: string
    subtitle: string
    count_prefix: string
    count_one: string
    count_other: string
    flash_added: string
    flash_removed: string
    empty: BookmarksEmptyTranslations
    closed: BookmarksClosedTranslations
}

export interface ClosedBookmarkCardData extends ListingCardData {
    status: ClosedListingStatusValue
}

export interface BookmarksIndexPageProps {
    listings: ListingCardData[]
    closedListings: ClosedBookmarkCardData[]
    translations: BookmarksTranslations
    listingTranslations: ListingsTranslations
    [key: string]: unknown
}
