import { listingExpiryText } from '@/Components/Listings/listingFormat'
import type { ListingExpiryNoteProps } from '@/types/listing'

export default function ListingExpiryNote({ listing, translations, className = '' }: ListingExpiryNoteProps) {
    const text = listingExpiryText(listing, translations)

    if (!text) {
        return null
    }

    return <span className={`text-[13px] text-ink-muted ${className}`}>{text}</span>
}
