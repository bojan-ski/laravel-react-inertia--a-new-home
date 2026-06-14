import type { FormEvent, ReactNode } from 'react'
import type { ListingStatusValue } from '@/types/listing'

export interface AgencyFormSections {
    identity: string
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
}

export interface AgencyFormTranslations {
    sections: AgencyFormSections
    fields: AgencyFormFields
}

export interface AgencyFormData {
    name: string
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
}

export type AgencyStatusValue = 'pending_verification' | 'approved' | 'rejected' | 'hidden'

export interface AgencyShowData {
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
}

export interface AgencyShowListingsTranslations {
    heading: string
    count: string
    count_one: string
    created: string
    approved: string
    per_month: string
    rejection_reason_label: string
    empty_headline: string
    empty_subline: string
    listing_types: Record<string, string>
    status: Record<ListingStatusValue, string>
}

export interface AgencyShowTranslations {
    title: string
    subtitle: string
    edit: string
    status: Record<AgencyStatusValue, AgencyStatusCopy>
    profile: AgencyShowProfileTranslations
    listings: AgencyShowListingsTranslations
}

export interface AgencyShowPageProps {
    agency: AgencyShowData
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
}

export interface AgencyListingRowProps {
    listing: AgencyDashboardListing
    translations: AgencyShowListingsTranslations
    propertyTypeLabels: Record<string, string>
}

export interface AgencyEditData {
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
    flash: string
}

export interface AgencyEditPageProps {
    agency: AgencyEditData
    translations: AgencyEditTranslations
}
