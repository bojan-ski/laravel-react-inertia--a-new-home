import type { ListingCloseTranslations, ListingExpiryTranslations, ListingStatusValue } from '@/types/listing'
import type { ConfirmActionTranslations } from '@/types/moderation'

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
    closedAt: string | null
    expiresAt: string | null
    isExpired: boolean
    canRenew: boolean
}

export type ProfileStatusLabels = Record<ListingStatusValue, string>

export interface ProfileEmptyTranslations {
    headline: string
    subline: string
    cta: string
}

export type ProfileConfirmTranslations = ConfirmActionTranslations

export interface ProfileTranslations extends ListingCloseTranslations, ListingExpiryTranslations {
    title: string
    subtitle: string
    new_listing: string
    created: string
    rejection_reason_label: string
    per_month: string
    edit: string
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
}

export interface ProfileSettingsDeleteTranslations {
    heading: string
    note: string
    password: string
    submit: string
    confirm: ConfirmActionTranslations
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
    password: ProfileSettingsPasswordTranslations
    delete: ProfileSettingsDeleteTranslations
}

export interface ProfileSettingsPageProps {
    translations: ProfileSettingsTranslations
    backHref: string
    passwordUrl: string
    deleteUrl: string
    [key: string]: unknown
}
