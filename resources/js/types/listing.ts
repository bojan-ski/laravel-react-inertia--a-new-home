import type { FormEvent, ReactNode } from 'react'
import type { ListingReportTranslations } from '@/types/listing-report'
import type { ConfirmActionTranslations } from '@/types/moderation'
import type { Paginator, PaginationLabels } from '@/types/pagination'
import type { SaveSearchTranslations } from '@/types/saved-search'
import type { ToggleOption } from '@/types/ui'

export type ListingStatusValue = 'pending_review' | 'approved' | 'rejected' | 'hidden' | 'sold' | 'rented'

export type ClosedListingStatusValue = Extract<ListingStatusValue, 'sold' | 'rented'>

export interface ListingCloseTranslations {
    closed_on: string
    mark_sold: string
    mark_rented: string
    reopen: string
    close_confirm: Record<'sale' | 'rent', ConfirmActionTranslations>
    reopen_confirm: ConfirmActionTranslations
}

export interface ListingCloseActionsProps {
    listing: {
        id: number
        title: string
        listingType: string
        status: ListingStatusValue
    }
    baseUrl: string
    translations: ListingCloseTranslations
}

export interface ListingExpiryTranslations {
    expires_on: string
    expires_in: string
    expires_in_one: string
    expired: string
    renew: string
}

export interface ListingExpiryData {
    id: number
    status: ListingStatusValue
    expiresAt: string | null
    isExpired: boolean
    canRenew: boolean
}

export interface ListingExpiryNoteProps {
    listing: ListingExpiryData
    translations: ListingExpiryTranslations
    className?: string
}

export interface ListingRenewButtonProps {
    listing: ListingExpiryData
    baseUrl: string
    translations: ListingExpiryTranslations
}

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
    canBookmark: boolean
}

export interface ListingFilters {
    search: string
    listingType: string
    propertyType: string
    city: string
    minPrice: string
    maxPrice: string
    minBedrooms: string
    minBathrooms: string
    minArea: string
    maxArea: string
    amenities: string[]
    sort: string
}

export interface ListingsFilterTranslations {
    search_placeholder: string
    search_label: string
    listing_type_label: string
    all: string
    buy: string
    rent: string
    property_type_label: string
    all_types: string
    price: string
    beds: string
    baths: string
    sort_label: string
    filters: string
    filters_count: string
    apply: string
    clear: string
    clear_all: string
    close: string
    search: string
    reset: string
    more_filters: string
    city_placeholder: string
    min_price: string
    max_price: string
    any_beds: string
    beds_option: string
    any_baths: string
    baths_option: string
    min_area: string
    max_area: string
    amenities_label: string
    property_types: Record<string, string>
    sorts: Record<string, string>
    sorts_short: Record<string, string>
}

export interface ListingsCardTranslations {
    for_sale: string
    for_rent: string
    per_month: string
    beds: string
    baths: string
    bedroom: string
    bedrooms: string
    bathroom: string
    bathrooms: string
    bd: string
    ba: string
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
    listed: string
    description_heading: string
    amenities_heading: string
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
    view_agency: string
    month_estimate: string
    gallery: ListingGalleryTranslations
    similar: SimilarListingsTranslations
    mortgage: MortgageCalculatorTranslations
}

export interface ListingGalleryTranslations {
    open: string
    all_photos: string
    dialog_label: string
    close: string
    close_text: string
    previous: string
    next: string
    counter: string
    thumbnail: string
}

export interface SimilarListingsTranslations {
    heading: string
    loading: string
    browse: string
}

export interface MortgageCalculatorTranslations {
    heading: string
    price: string
    down_payment: string
    interest_rate: string
    term: string
    term_option: string
    term_option_short: string
    monthly_payment: string
    per_month: string
    loan_amount: string
    total_interest: string
    unavailable: string
    disclaimer: string
}

export interface MortgageDefaults {
    down_payment_percent: number
    interest_rate: number
    term_years: number
    term_options: number[]
}

export interface MortgageEstimate {
    loanAmount: number
    monthlyPayment: number
    totalInterest: number
}

export interface MortgageState {
    priceInput: string
    setPriceInput: (value: string) => void
    downPaymentInput: string
    setDownPaymentInput: (value: string) => void
    rateInput: string
    setRateInput: (value: string) => void
    termYears: number
    setTermYears: (years: number) => void
    estimate: MortgageEstimate | null
}

export interface MortgageCalculatorProps {
    mortgage: MortgageState
    termOptions: number[]
    translations: MortgageCalculatorTranslations
    variant: 'full' | 'compact'
}

export interface ListingsTranslations {
    title: string
    count: string
    count_one: string
    count_short: string
    count_short_one: string
    filters: ListingsFilterTranslations
    card: ListingsCardTranslations
    empty: ListingsEmptyTranslations
    pagination: PaginationLabels
    show: ListingsShowTranslations
    amenities: Record<string, string>
}

export interface ListingsIndexPageProps {
    listings: Paginator<ListingCardData>
    filters: ListingFilters
    canSaveSearch: boolean
    suggestedSearchName: string
    searchSummary: string[]
    saveSearchTranslations: SaveSearchTranslations
    translations: ListingsTranslations
    [key: string]: unknown
}

export interface ListingImageData {
    id: number
    url: string
    thumbUrl: string
    altText: string
    sortOrder: number
}

export interface ListingAgencyLink {
    name: string
    slug: string
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
    agency: ListingAgencyLink | null
    amenities: string[]
    approvedAt: string | null
    images: ListingImageData[]
}

export interface ListingShowPageProps {
    listing: ListingShowData
    isBookmarked: boolean
    canBookmark: boolean
    canStartChat: boolean
    canReport: boolean
    similarListings?: ListingCardData[]
    mortgageDefaults: MortgageDefaults
    translations: ListingsTranslations
    reportTranslations: ListingReportTranslations
    [key: string]: unknown
}

export interface ListingGalleryProps {
    images: ListingImageData[]
    title: string
    translations: ListingGalleryTranslations
}

export interface ListingImagePlaceholderProps {
    className?: string
}

export interface ImageManagementTranslations {
    heading: string
    helper: string
    helper_empty: string
    delete: string
    choose_image: string
    no_file: string
    alt_label: string
    alt_placeholder: string
    upload: string
    uploading: string
    format_note: string
    cover: string
    no_alt: string
    image_label: string
}

export interface ListingFormImageData {
    id: number
    url: string
    thumbUrl: string
    altText: string | null
    sortOrder: number
}

export interface ImageUploaderProps {
    baseUrl: string
    images: ListingFormImageData[]
    listingTitle: string
    translations: ImageManagementTranslations
}

export type ImageUploadFormData = {
    image: File | null
    alt_text: string
}

export interface ListingFactsProps {
    listing: ListingShowData
    translations: ListingsShowTranslations
}

export interface ListingDetailHeaderProps {
    listing: ListingShowData
    translations: ListingsShowTranslations
    propertyLabel: string
    showBack?: boolean
}

export interface ListingDetailSectionsProps {
    listing: ListingShowData
    translations: ListingsShowTranslations
    propertyLabel: string
    amenityLabels: Record<string, string>
    mortgage?: ReactNode
    sidebar: ReactNode
    footer?: ReactNode
}

export interface ListingDetailBlockProps {
    heading: string
    headingClassName?: string
    className?: string
    children: ReactNode
}

export interface ListingSidebarProps {
    listing: ListingShowData
    translations: ListingsShowTranslations
    actions?: ReactNode
    footer?: ReactNode
    className?: string
}

export interface ListingDetailActionsProps {
    listingId: number
    isBookmarked: boolean
    canBookmark: boolean
    canStartChat: boolean
    translations: ListingsShowTranslations
}

export interface ListingBookmarkHeartProps {
    listingId: number
    isBookmarked: boolean
    translations: ListingsShowTranslations
}

export interface ListingMobileBarProps {
    listing: ListingShowData
    translations: ListingsShowTranslations
    monthlyPayment: number | null
}

export interface ListingContactBlockProps {
    listing: ListingShowData
    translations: ListingsShowTranslations
}

export interface ListingDetailRowProps {
    label: string
    value: string
}

export interface ListingAmenitiesProps {
    amenities: string[]
    labels: Record<string, string>
}

export interface ListingDescriptionProps {
    description: string
}

export interface ListingTeaserData {
    id: number
    title: string
    slug: string
    listingType: string
    price: string
    currency: string
    city: string
    country: string
    imageUrl: string | null
}

export interface ListingTeaserCardProps {
    listing: ListingTeaserData
    perMonth: string
    aspect: '3/4' | '4/3'
    mobileWidth: string
}

export type SetListingFilter = <K extends keyof ListingFilters>(key: K, value: ListingFilters[K]) => void

export interface ListingFilterBarProps {
    filters: ListingFilters
    translations: ListingsFilterTranslations
    amenityLabels: Record<string, string>
    actionsSlot?: ReactNode
}

export interface ListingFilterFieldsProps {
    form: ListingFilters
    set: SetListingFilter
    translations: ListingsFilterTranslations
    idPrefix: string
}

export interface ListingAmenityChipsProps extends ListingFilterFieldsProps {
    amenityLabels: Record<string, string>
}

export type ListingMoreFiltersProps = ListingAmenityChipsProps

export interface ListingTextSelectProps {
    id: string
    label: string
    value: string
    onChange: (value: string) => void
    options: ToggleOption[]
    active?: boolean
    className?: string
}

export interface ListingFilterActionsProps {
    clearLabel: string
    onClear: () => void
    applyLabel: string
    applyForm?: string
}

export interface ListingFiltersSheetProps {
    open: boolean
    onClose: () => void
    filters: ListingFilters
    translations: ListingsFilterTranslations
    amenityLabels: Record<string, string>
    onApply: (filters: ListingFilters) => void
    onClearAll: () => void
}

export interface ListingRowProps {
    listing: ListingCardData
    translations: ListingsCardTranslations
    propertyTypeLabels: Record<string, string>
}

export interface ListingSaveButtonProps {
    listing: ListingCardData
    translations: ListingsCardTranslations
    className?: string
}

export interface ListingCardProps {
    listing: ListingCardData
    translations: ListingsCardTranslations
    propertyTypeLabels: Record<string, string>
    closedLabel?: string
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
    description: string
    country: string
    city: string
    address_line: string
    postal_code: string
    bedrooms: string
    bathrooms: string
    area_value: string
    year_built: string
    amenities: string[]
    contact_name: string
    contact_email: string
    contact_phone: string
}

export interface ListingFormFieldLabels {
    title: string
    listing_type: string
    property_type: string
    price: string
    description: string
    country: string
    country_placeholder: string
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
    sections_label: string
    sections: {
        basic: string
        images: string
        location: string
        specs: string
        amenities: string
        contact: string
    }
    listing_types: {
        sale: string
        rent: string
    }
    fields: ListingFormFieldLabels
}

export interface NewListingImagesTranslations {
    heading: string
    helper: string
    choose: string
    remove: string
    count: string
    format_note: string
    cover: string
}

export type CreateListingFormData = ListingFormData & {
    images: File[]
}

export interface NewListingImagePickerProps {
    files: File[]
    onChange: (files: File[]) => void
    errors: Partial<Record<string, string>>
    maxImages: number
    translations: NewListingImagesTranslations
}

export interface CreateListingTranslations extends ListingFormTranslations {
    title: string
    subtitle: string
    back: string
    submit: string
    new_images: NewListingImagesTranslations
}

export interface CreateListingPageProps {
    translations: CreateListingTranslations
    propertyTypeLabels: Record<string, string>
    amenityLabels: Record<string, string>
    countryOptions: string[]
    maxImages: number
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
    amenities: string[]
    images: ListingFormImageData[]
}

export interface EditListingTranslations extends ListingFormTranslations {
    title: string
    subtitle: string
    back: string
    submit: string
    approved_notice: string
    bar_note_approved: string
    images: ImageManagementTranslations
}

export interface EditListingPageProps {
    listing: EditListingData
    translations: EditListingTranslations
    propertyTypeLabels: Record<string, string>
    amenityLabels: Record<string, string>
    countryOptions: string[]
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
    amenityLabels: Record<string, string>
    countryOptions: string[]
    imagesSection: ReactNode
    imagesNote: string
    barNote: string
    contactNote?: string
    beforeSections?: ReactNode
    hideContactSection?: boolean
    cancelHref?: string
}

export interface ListingFormHeaderProps {
    title: string
    subtitle: string
    back: string
    backHref: string
}

export interface ListingApprovedNoticeProps {
    message: string
}

export interface ListingCoverTagProps {
    label: string
}

export interface CreateAgencyListingTranslations extends ListingFormTranslations {
    title: string
    subtitle: string
    back: string
    submit: string
    contact_inheritance_note: string
    new_images: NewListingImagesTranslations
}

export interface CreateAgencyListingPageProps {
    translations: CreateAgencyListingTranslations
    propertyTypeLabels: Record<string, string>
    amenityLabels: Record<string, string>
    countryOptions: string[]
    maxImages: number
    [key: string]: unknown
}

export interface EditAgencyListingTranslations extends ListingFormTranslations {
    title: string
    subtitle: string
    back: string
    submit: string
    approved_notice: string
    bar_note_approved: string
    contact_inheritance_note: string
    images: ImageManagementTranslations
}

export interface EditAgencyListingPageProps {
    listing: EditListingData
    translations: EditAgencyListingTranslations
    propertyTypeLabels: Record<string, string>
    amenityLabels: Record<string, string>
    countryOptions: string[]
    [key: string]: unknown
}
