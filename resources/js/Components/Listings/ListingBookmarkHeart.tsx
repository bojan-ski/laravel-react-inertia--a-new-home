import { Heart } from 'lucide-react'
import { useBookmarkToggle } from '@/Components/Listings/useListingActions'
import type { ListingBookmarkHeartProps } from '@/types/listing'

export default function ListingBookmarkHeart({ listingId, isBookmarked, translations }: ListingBookmarkHeartProps) {
    const bookmark = useBookmarkToggle(listingId)

    return (
        <button
            type="button"
            onClick={bookmark.toggle}
            aria-disabled={bookmark.processing || undefined}
            aria-pressed={isBookmarked}
            aria-label={isBookmarked ? translations.saved : translations.save_listing}
            className="flex size-11 items-center justify-center text-forest aria-disabled:opacity-45"
        >
            <Heart className={`size-5 ${isBookmarked ? 'fill-forest' : ''}`} strokeWidth={1.25} aria-hidden="true" />
        </button>
    )
}
