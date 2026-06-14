import type { ListingStatusValue } from '@/types/listing'

export interface ProfileListingData {
    id: number
    title: string
    listingType: string
    propertyType: string
    price: string
    currency: string
    city: string
    country: string
    imageUrl: string | null
    status: ListingStatusValue
    createdAt: string
    rejectionReason: string | null
}

export interface ProfileStatusLabels {
    pending_review: string
    approved: string
    rejected: string
    hidden: string
}

export interface ProfileEmptyTranslations {
    headline: string
    subline: string
    cta: string
}

export interface ProfileConfirmTranslations {
    title: string
    description: string
    cancel: string
    confirm: string
}

export interface ProfileTranslations {
    title: string
    subtitle: string
    account_settings: string
    new_listing: string
    created: string
    rejection_reason_label: string
    per_month: string
    delete: string
    listing_types: Record<string, string>
    status: ProfileStatusLabels
    empty: ProfileEmptyTranslations
    confirm: ProfileConfirmTranslations
}

export interface ProfileIndexPageProps {
    listings: ProfileListingData[]
    translations: ProfileTranslations
    propertyTypeLabels: Record<string, string>
    [key: string]: unknown
}

export interface ProfileListingRowProps {
    listing: ProfileListingData
    translations: ProfileTranslations
    propertyTypeLabels: Record<string, string>
}

export interface ProfileSettingsUser {
    id: number
    name: string
    email: string
}

export interface ProfileSettingsAccountTranslations {
    heading: string
    note: string
    name: string
    email: string
    submit: string
    flash: string
}

export interface ProfileSettingsPasswordTranslations {
    heading: string
    note: string
    current: string
    new: string
    confirm: string
    submit: string
    flash: string
}

export interface ProfileSettingsTranslations {
    title: string
    subtitle: string
    back: string
    account: ProfileSettingsAccountTranslations
    password: ProfileSettingsPasswordTranslations
}

export interface ProfileSettingsPageProps {
    user: ProfileSettingsUser
    translations: ProfileSettingsTranslations
    [key: string]: unknown
}
