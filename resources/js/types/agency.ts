import type { FormEvent, ReactNode } from 'react'
import type {
    ListingCardData,
    ListingCloseTranslations,
    ListingExpiryTranslations,
    ListingStatusValue,
    ListingsTranslations,
} from '@/types/listing'
import type { ConfirmActionTranslations } from '@/types/moderation'
import type { PaginationLabels, Paginator } from '@/types/pagination'

export interface AgencyRegistrationDetails {
    vatId: string | null
    registrationNumber: string | null
    registrationCountry: string | null
    brokerRegisterNumber: string | null
}

export interface AgencyBusinessDetails extends AgencyRegistrationDetails {
    legalName: string | null
}

export interface AgencyRegistrationLabels {
    registration: string
    broker_register: string
    vat_id: string
}

export interface AgencyFormSections {
    identity: string
    business: string
    contact: string
}

export interface AgencyFormFields {
    name: string
    description: string
    email: string
    email_note: string
    phone: string
    website: string
    address: string
    legal_name: string
    vat_id: string
    vat_id_placeholder: string
    registration_number: string
    registration_country: string
    registration_country_placeholder: string
    broker_register_number: string
    business_note: string
}

export interface AgencyFormTranslations {
    sections: AgencyFormSections
    fields: AgencyFormFields
}

export interface AgencyFormData {
    name: string
    legal_name: string
    vat_id: string
    registration_number: string
    registration_country: string
    broker_register_number: string
    email: string
    phone: string
    website: string
    address: string
    description: string
}

export interface AgencyFormProps {
    data: AgencyFormData
    setData: <K extends keyof AgencyFormData>(key: K, value: AgencyFormData[K]) => void
    errors: Partial<Record<keyof AgencyFormData, string>>
    processing: boolean
    submitLabel: string
    onSubmit: (event: FormEvent<HTMLFormElement>) => void
    translations: AgencyFormTranslations
    countries: Record<string, string>
    note?: ReactNode
    cancelSlot?: ReactNode
}

export interface AgencySetupTranslations extends AgencyFormTranslations {
    title: string
    subtitle: string
    helper: string
    submit: string
    back: string
    submit_note: string
    flash: string
}

export interface AgencySetupPageProps {
    translations: AgencySetupTranslations
    countries: Record<string, string>
}

export type AgencyStatusValue = 'pending_verification' | 'approved' | 'rejected' | 'hidden'

export interface AgencyShowData extends AgencyBusinessDetails {
    id: number
    name: string
    slug: string
    description: string | null
    phone: string | null
    email: string
    website: string | null
    address: string | null
    status: AgencyStatusValue
    rejectionReason: string | null
    approvedAt: string | null
}

export interface AgencyDashboardListing {
    id: number
    title: string
    slug: string
    listingType: string
    propertyType: string
    status: ListingStatusValue
    price: string
    currency: string
    city: string
    country: string
    createdAt: string | null
    approvedAt: string | null
    rejectionReason: string | null
    closedAt: string | null
    expiresAt: string | null
    isExpired: boolean
    canRenew: boolean
}

export interface AgencyStatusCopy {
    heading: string
    body: string
    note?: string
    approved_on?: string
}

export interface AgencyShowProfileTranslations {
    heading: string
    note: string
    name: string
    description: string
    email: string
    phone: string
    website: string
    address: string
    legal_name: string
    vat_id: string
    registration: string
    registration_value: string
    broker_register_number: string
    not_set: string
    missing: string
}

export type AgencyListingsConfirmTranslations = ConfirmActionTranslations

export interface AgencyShowListingsTranslations extends ListingCloseTranslations, ListingExpiryTranslations {
    heading: string
    count: string
    count_one: string
    created: string
    approved: string
    per_month: string
    rejection_reason_label: string
    new_listing: string
    new_listing_disabled_help: string
    create_listing: string
    delete: string
    empty_headline: string
    empty_subline: string
    empty_headline_approved: string
    empty_subline_approved: string
    listing_types: Record<string, string>
    status: Record<ListingStatusValue, string>
    confirm: AgencyListingsConfirmTranslations
}

export interface AgencyShowTranslations {
    title: string
    subtitle: string
    edit: string
    settings: string
    pending_changes: string
    status: Record<AgencyStatusValue, AgencyStatusCopy>
    profile: AgencyShowProfileTranslations
    listings: AgencyShowListingsTranslations
}

export interface AgencyPendingChange {
    submittedAt: string
    changes: Partial<Record<keyof AgencyFormData, string | null>>
}

export interface AgencyShowPageProps {
    agency: AgencyShowData
    pendingChange: AgencyPendingChange | null
    listings: AgencyDashboardListing[]
    translations: AgencyShowTranslations
    propertyTypeLabels: Record<string, string>
}

export interface AgencyStatusPanelProps {
    agency: AgencyShowData
    translations: AgencyShowTranslations
}

export interface AgencyProfileSectionProps {
    agency: AgencyShowData
    translations: AgencyShowProfileTranslations
}

export interface AgencyListingsSectionProps {
    listings: AgencyDashboardListing[]
    translations: AgencyShowListingsTranslations
    propertyTypeLabels: Record<string, string>
    agencyStatus: AgencyStatusValue
}

export interface AgencyListingRowProps {
    listing: AgencyDashboardListing
    translations: AgencyShowListingsTranslations
    propertyTypeLabels: Record<string, string>
}

export interface AgencyEditData extends AgencyBusinessDetails {
    id: number
    name: string
    description: string | null
    phone: string | null
    email: string
    website: string | null
    address: string | null
    status: AgencyStatusValue
}

export interface AgencyEditTranslations extends AgencyFormTranslations {
    title: string
    subtitle: string
    back: string
    submit: string
    cancel: string
    status_note: string
    review_note: string
    pending_notice: string
    flash: string
}

export interface AgencyEditPageProps {
    agency: AgencyEditData
    pendingChange: AgencyPendingChange | null
    translations: AgencyEditTranslations
    countries: Record<string, string>
}

export interface AgencyCardData extends AgencyRegistrationDetails {
    id: number
    name: string
    slug: string
    email: string
    phone: string | null
    website: string | null
    address: string | null
    description: string | null
    approvedAt: string | null
}

export interface AgenciesFilters {
    search: string | null
}

export interface AgenciesSearchTranslations {
    placeholder: string
    submit: string
    reset: string
}

export interface AgenciesEmptyTranslations {
    headline: string
    hint: string
    reset: string
    none: string
}

export interface AgenciesDirectoryTranslations extends AgencyRegistrationLabels {
    title: string
    heading: string
    subtitle: string
    count_prefix: string
    count_suffix: string
    view_agency: string
    show: AgencyPublicPageTranslations
    search: AgenciesSearchTranslations
    empty: AgenciesEmptyTranslations
    pagination: PaginationLabels
}

export interface AgencyPublicPageTranslations {
    back: string
    eyebrow: string
    member_since: string
    contact_heading: string
    listings_heading: string
    listings_count_one: string
    listings_count_other: string
    empty: string
}

export interface AgenciesIndexPageProps {
    agencies: Paginator<AgencyCardData>
    filters: AgenciesFilters
    translations: AgenciesDirectoryTranslations
}

export interface AgencyPublicPageProps {
    agency: AgencyCardData
    listings: Paginator<ListingCardData>
    translations: AgenciesDirectoryTranslations
    listingTranslations: ListingsTranslations
}

export interface AgencyCardProps {
    agency: AgencyCardData
    viewLabel: string
    registrationLabels: AgencyRegistrationLabels
}

export interface AgencyContactListProps {
    agency: AgencyCardData
}

export interface AgencyDirectorySearchBarProps {
    filters: AgenciesFilters
    translations: AgenciesSearchTranslations
}

export interface AgenciesEmptyStateProps {
    translations: AgenciesEmptyTranslations
    hasActiveSearch: boolean
}
