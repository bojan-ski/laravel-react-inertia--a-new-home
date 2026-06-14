import type { FormEvent, ReactNode } from 'react'
import type { Paginator, PaginationLabels } from '@/types/pagination'

export type ListingStatusValue = 'pending_review' | 'approved' | 'rejected' | 'hidden'

export interface ListingCardData {
    id: number
    title: string
    slug: string
    listingType: string
    propertyType: string
    price: string
    currency: string
    city: string
    country: string
    bedrooms: number | null
    bathrooms: number | null
    areaValue: string | null
    areaUnit: string | null
    approvedAt: string | null
    imageUrl: string | null
    ownerName: string | null
    isBookmarked: boolean
}

export interface ListingFilters {
    search: string
    listingType: string
    propertyType: string
    city: string
    minPrice: string
    maxPrice: string
    sort: string
}

export interface ListingsNavTranslations {
    back: string
    brand: string
    listings: string
    agencies: string
    favorites: string
    login: string
    sign_up: string
    dashboard: string
    sign_out: string
    open_menu: string
    close_menu: string
}

export interface ListingsNavLink {
    label: string
    href: string
}

export interface ListingsFilterTranslations {
    search_placeholder: string
    all: string
    buy: string
    rent: string
    all_types: string
    search: string
    reset: string
    more_filters: string
    city_placeholder: string
    min_price: string
    max_price: string
    property_types: Record<string, string>
    sorts: Record<string, string>
}

export interface ListingsCardTranslations {
    for_sale: string
    for_rent: string
    per_month: string
    beds: string
    baths: string
    save: string
    saved: string
}

export interface ListingsEmptyTranslations {
    headline: string
    hint: string
    reset: string
}

export interface ListingsShowTranslations {
    back: string
    for_sale: string
    for_rent: string
    bed: string
    beds: string
    bath: string
    baths: string
    built: string
    description_heading: string
    details_heading: string
    detail_address: string
    detail_property_type: string
    detail_listing_type: string
    detail_year_built: string
    detail_listed: string
    per_month: string
    save_listing: string
    saved: string
    start_chat: string
    contact_heading: string
}

export interface ListingsTranslations {
    title: string
    count_prefix: string
    count_suffix: string
    nav: ListingsNavTranslations
    filters: ListingsFilterTranslations
    card: ListingsCardTranslations
    empty: ListingsEmptyTranslations
    pagination: PaginationLabels
    show: ListingsShowTranslations
}

export interface ListingsIndexPageProps {
    listings: Paginator<ListingCardData>
    filters: ListingFilters
    translations: ListingsTranslations
    [key: string]: unknown
}

export interface ListingImageData {
    id: number
    url: string
    altText: string
    sortOrder: number
}

export interface ListingShowData {
    id: number
    title: string
    slug: string
    listingType: string
    propertyType: string
    price: string
    currency: string
    description: string
    country: string
    city: string
    addressLine: string | null
    postalCode: string | null
    bedrooms: number | null
    bathrooms: number | null
    areaValue: string | null
    areaUnit: string | null
    yearBuilt: number | null
    contactName: string | null
    contactEmail: string | null
    contactPhone: string | null
    approvedAt: string | null
    images: ListingImageData[]
}

export interface ListingShowPageProps {
    listing: ListingShowData
    isBookmarked: boolean
    canStartChat: boolean
    translations: ListingsTranslations
    [key: string]: unknown
}

export interface ListingGalleryProps {
    images: ListingImageData[]
}

export interface ListingFactsProps {
    listing: ListingShowData
    translations: ListingsShowTranslations
}

export interface ListingSidebarProps {
    listing: ListingShowData
    isBookmarked: boolean
    canStartChat: boolean
    translations: ListingsShowTranslations
}

export interface ListingDetailRowProps {
    label: string
    value: string
}

export interface ListingsLayoutProps {
    nav: ListingsNavTranslations
    children: ReactNode
}

export interface ListingsNavbarProps {
    nav: ListingsNavTranslations
}

export interface ListingsNavbarAuthActionsProps {
    nav: ListingsNavTranslations
    onNavigate?: () => void
}

export interface ListingsNavbarMobilePanelProps {
    nav: ListingsNavTranslations
    links: ListingsNavLink[]
    currentPath: string
    onClose: () => void
}

export interface ListingFilterBarProps {
    filters: ListingFilters
    translations: ListingsFilterTranslations
}

export interface ListingCardProps {
    listing: ListingCardData
    translations: ListingsCardTranslations
    propertyTypeLabels: Record<string, string>
}

export interface ListingsEmptyStateProps {
    translations: ListingsEmptyTranslations
    hasActiveFilters: boolean
}

export type ListingFormData = {
    title: string
    listing_type: string
    property_type: string
    price: string
    currency: string
    description: string
    country: string
    city: string
    address_line: string
    postal_code: string
    bedrooms: string
    bathrooms: string
    area_value: string
    area_unit: string
    year_built: string
    contact_name: string
    contact_email: string
    contact_phone: string
}

export interface ListingFormFieldLabels {
    title: string
    listing_type: string
    property_type: string
    price: string
    currency: string
    description: string
    country: string
    city: string
    address_line: string
    postal_code: string
    bedrooms: string
    bathrooms: string
    area: string
    year_built: string
    contact_name: string
    contact_email: string
    contact_phone: string
}

export interface ListingFormTranslations {
    cancel: string
    submit_note: string
    contact_note: string
    sections: {
        basic: string
        location: string
        specs: string
        contact: string
    }
    listing_types: {
        sale: string
        rent: string
    }
    fields: ListingFormFieldLabels
}

export interface CreateListingTranslations extends ListingFormTranslations {
    title: string
    subtitle: string
    back: string
    submit: string
}

export interface CreateListingPageProps {
    translations: CreateListingTranslations
    propertyTypeLabels: Record<string, string>
    [key: string]: unknown
}

export interface EditListingData {
    id: number
    title: string
    listingType: string
    propertyType: string
    status: ListingStatusValue
    price: string
    currency: string
    description: string
    country: string
    city: string
    addressLine: string | null
    postalCode: string | null
    bedrooms: number | null
    bathrooms: number | null
    areaValue: string | null
    areaUnit: string | null
    yearBuilt: number | null
    contactName: string | null
    contactEmail: string | null
    contactPhone: string | null
    images: ListingImageData[]
}

export interface EditListingTranslations extends ListingFormTranslations {
    title: string
    subtitle: string
    back: string
    submit: string
    approved_notice: string
}

export interface EditListingPageProps {
    listing: EditListingData
    translations: EditListingTranslations
    propertyTypeLabels: Record<string, string>
    [key: string]: unknown
}

export interface ListingFormProps {
    data: ListingFormData
    setData: <K extends keyof ListingFormData>(key: K, value: ListingFormData[K]) => void
    errors: Partial<Record<keyof ListingFormData, string>>
    processing: boolean
    submitLabel: string
    onSubmit: (event: FormEvent<HTMLFormElement>) => void
    translations: ListingFormTranslations
    propertyTypeLabels: Record<string, string>
    headerSlot?: ReactNode
}
