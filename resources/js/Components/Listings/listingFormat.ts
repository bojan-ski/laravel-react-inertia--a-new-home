import type { ListingExpiryData, ListingExpiryTranslations, ListingShowData } from '@/types/listing'

const DAY_MS = 24 * 60 * 60 * 1000

export function listingExpiryText(listing: ListingExpiryData, translations: ListingExpiryTranslations): string | null {
    if (listing.status !== 'approved' || listing.isExpired || !listing.expiresAt) {
        return null
    }

    if (!listing.canRenew) {
        return translations.expires_on.replace(':date', formatDate(listing.expiresAt))
    }

    const days = Math.max(1, Math.ceil((new Date(listing.expiresAt).getTime() - Date.now()) / DAY_MS))

    return days === 1 ? translations.expires_in_one : translations.expires_in.replace(':days', String(days))
}

export function hasListingContact(listing: ListingShowData): boolean {
    return Boolean(listing.contactName || listing.contactEmail || listing.contactPhone)
}

const currencySymbols: Record<string, string> = {
    EUR: '€',
}

export function formatPrice(price: string, currency: string): string {
    const amount = Number(price)
    const formatted = Number.isNaN(amount)
        ? price
        : amount.toLocaleString('en-US', { maximumFractionDigits: 0 })
    const symbol = currencySymbols[currency]

    return symbol ? `${symbol}${formatted}` : `${currency} ${formatted}`
}

export function formatArea(value: string, unit: string | null): string {
    const amount = Number(value)
    const formatted = Number.isNaN(amount) ? value : amount.toLocaleString('en-US')

    return unit ? `${formatted} ${unit}` : formatted
}

export function countLabel(count: number, one: string, other: string): string {
    return (count === 1 ? one : other).replace(':count', count.toLocaleString('en-US'))
}

export function formatDate(iso: string | null, style: 'short' | 'long' = 'short'): string {
    if (!iso) {
        return ''
    }

    return new Date(iso).toLocaleDateString('en-GB', {
        month: style,
        day: 'numeric',
        year: 'numeric',
    })
}

export function formatDateTime(iso: string | null): string {
    if (!iso) {
        return ''
    }

    return new Date(iso).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    })
}
