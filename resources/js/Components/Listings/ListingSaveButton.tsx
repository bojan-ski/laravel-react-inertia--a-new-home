import { Heart } from 'lucide-react'
import { useBookmarkToggle } from '@/Components/Listings/useListingActions'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { ListingSaveButtonProps } from '@/types/listing'

export default function ListingSaveButton({ listing, translations, className = '' }: ListingSaveButtonProps) {
    const actionLabel = useActionLabel()
    const bookmark = useBookmarkToggle(listing.id)

    if (!listing.canBookmark) {
        return null
    }

    return (
        <button
            type="button"
            aria-pressed={listing.isBookmarked}
            aria-disabled={bookmark.processing || undefined}
            aria-label={actionLabel(listing.isBookmarked ? translations.saved : translations.save, listing.title)}
            onClick={bookmark.toggle}
            className={`relative z-10 inline-flex min-h-11 items-center gap-1.5 text-[13px] text-ink/60 transition-colors duration-150 hover:text-forest ${className}`}
        >
            {listing.isBookmarked ? (
                <>
                    <Heart className="size-3 fill-forest text-forest" strokeWidth={1.25} aria-hidden="true" />
                    {translations.saved}
                </>
            ) : (
                translations.save
            )}
        </button>
    )
}
