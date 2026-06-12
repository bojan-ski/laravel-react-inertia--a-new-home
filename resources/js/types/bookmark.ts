import type { ListingCardData, ListingsTranslations } from '@/types/listing'

export interface BookmarksEmptyTranslations {
    headline: string
    cta: string
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
}

export interface BookmarksIndexPageProps {
    listings: ListingCardData[]
    translations: BookmarksTranslations
    listingTranslations: ListingsTranslations
    [key: string]: unknown
}
