import { router } from '@inertiajs/react'
import { Heart } from 'lucide-react'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { ListingSaveButtonProps } from '@/types/listing'

export default function ListingSaveButton({ listing, translations, className = '' }: ListingSaveButtonProps) {
    const actionLabel = useActionLabel()

    if (!listing.canBookmark) {
        return null
    }

    return (
        <button
            type="button"
            aria-pressed={listing.isBookmarked}
            aria-label={actionLabel(listing.isBookmarked ? translations.saved : translations.save, listing.title)}
            onClick={() => router.post(`/listings/${listing.id}/bookmark`, {}, { preserveScroll: true })}
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
