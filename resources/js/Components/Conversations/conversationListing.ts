import { formatPrice } from '@/Components/Listings/listingFormat'
import type { ConversationListingSummary, ConversationsTranslations } from '@/types/conversation'

export function isListingRemoved(listing: ConversationListingSummary): boolean {
    return listing.id === null
}

export function listingStatusLabel(
    listing: ConversationListingSummary,
    translations: ConversationsTranslations,
): string | null {
    if (isListingRemoved(listing)) {
        return null
    }

    if (listing.status === 'sold' || listing.status === 'rented') {
        return translations.listing_status[listing.status]
    }

    return listing.isExpired ? translations.listing_status.expired : null
}

export function isListingDimmed(listing: ConversationListingSummary): boolean {
    return (
        isListingRemoved(listing) || listing.status === 'sold' || listing.status === 'rented' || listing.isExpired
    )
}

export function conversationListingLine(
    listing: ConversationListingSummary,
    translations: ConversationsTranslations,
): string {
    if (isListingRemoved(listing)) {
        return translations.listing_removed
    }

    return [listing.title, listing.city, listingStatusLabel(listing, translations)].filter(Boolean).join(' · ')
}

export function conversationPriceLine(
    listing: ConversationListingSummary,
    translations: ConversationsTranslations,
): string | null {
    if (isListingRemoved(listing)) {
        return translations.listing_removed
    }

    const price =
        listing.price !== null && listing.currency !== null
            ? `${formatPrice(listing.price, listing.currency)}${listing.listingType === 'rent' ? translations.per_month : ''}`
            : null

    return [price, listingStatusLabel(listing, translations)].filter(Boolean).join(' · ') || null
}
