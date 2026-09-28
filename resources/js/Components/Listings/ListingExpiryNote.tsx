import { formatDate } from '@/Components/Listings/listingFormat'
import type { ListingExpiryNoteProps } from '@/types/listing'

const DAY_MS = 24 * 60 * 60 * 1000

export default function ListingExpiryNote({ listing, translations }: ListingExpiryNoteProps) {
    if (listing.status !== 'approved' || listing.isExpired || !listing.expiresAt) {
        return null
    }

    if (!listing.canRenew) {
        return (
            <p className="mt-1 text-xs text-text-muted">
                {translations.expires_on.replace(':date', formatDate(listing.expiresAt))}
            </p>
        )
    }

    const days = Math.max(1, Math.ceil((new Date(listing.expiresAt).getTime() - Date.now()) / DAY_MS))

    return (
        <p className="mt-1 text-xs font-medium text-status-pending-review">
            {days === 1 ? translations.expires_in_one : translations.expires_in.replace(':days', String(days))}
        </p>
    )
}
