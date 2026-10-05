import { Heart } from 'lucide-react'
import { useBookmarkToggle, useStartChat } from '@/Components/Listings/useListingActions'
import Button from '@/Components/UI/Button'
import type { ListingDetailActionsProps } from '@/types/listing'

export default function ListingDetailActions({
    listingId,
    isBookmarked,
    canBookmark,
    canStartChat,
    translations,
}: ListingDetailActionsProps) {
    const bookmark = useBookmarkToggle(listingId)
    const chat = useStartChat(listingId)

    if (!canBookmark && !canStartChat) {
        return null
    }

    return (
        <div className="flex flex-col gap-2.5">
            {canStartChat && (
                <Button
                    fullWidth
                    className="min-h-13"
                    onClick={chat.start}
                    disabled={chat.processing}
                    trailing={<span aria-hidden="true">→</span>}
                >
                    {translations.start_chat}
                </Button>
            )}

            {canBookmark && (
                <Button
                    variant="secondary"
                    fullWidth
                    className="min-h-13"
                    onClick={bookmark.toggle}
                    aria-disabled={bookmark.processing || undefined}
                    aria-pressed={isBookmarked}
                    trailing={
                        <Heart
                            className={`size-4.5 text-forest ${isBookmarked ? 'fill-forest' : ''}`}
                            strokeWidth={1.25}
                            aria-hidden="true"
                        />
                    }
                >
                    {isBookmarked ? translations.saved : translations.save_listing}
                </Button>
            )}
        </div>
    )
}
