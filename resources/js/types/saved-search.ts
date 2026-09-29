import type { ConfirmActionTranslations } from '@/types/moderation'
import type { ListingFilters } from '@/types/listing'

export interface SaveSearchTranslations {
    action: string
    title: string
    description: string
    name_label: string
    alerts_label: string
    cancel: string
    submit: string
}

export interface SavedSearchesEmptyTranslations {
    headline: string
    hint: string
    cta: string
}

export interface SavedSearchUnsubscribeTranslations {
    eyebrow: string
    title: string
    lead: string
    confirm: string
    processing: string
    done: string
    done_lead: string
    manage: string
}

export interface SavedSearchesTranslations {
    title: string
    subtitle: string
    back: string
    count: string
    created: string
    alerts_label: string
    alerts_on: string
    alerts_off: string
    view_listings: string
    rename: string
    rename_label: string
    save_name: string
    cancel: string
    delete: string
    no_filters: string
    confirm_delete: ConfirmActionTranslations
    empty: SavedSearchesEmptyTranslations
    save: SaveSearchTranslations
}

export interface SavedSearchData {
    id: number
    name: string
    filters: Partial<Omit<ListingFilters, 'sort'>>
    alertsEnabled: boolean
    createdAt: string | null
    url: string
    summary: string[]
}

export interface SavedSearchesIndexPageProps {
    searches: SavedSearchData[]
    maxSearches: number
    translations: SavedSearchesTranslations
    [key: string]: unknown
}

export interface SavedSearchCardProps {
    search: SavedSearchData
    translations: SavedSearchesTranslations
}

export interface SaveSearchDialogProps {
    open: boolean
    onClose: () => void
    filters: ListingFilters
    suggestedName: string
    summary: string[]
    translations: SaveSearchTranslations
}

export interface SaveSearchButtonProps {
    filters: ListingFilters
    suggestedName: string
    summary: string[]
    translations: SaveSearchTranslations
}

export interface SavedSearchUnsubscribePageProps {
    confirmUrl: string
    translations: SavedSearchUnsubscribeTranslations
    [key: string]: unknown
}
