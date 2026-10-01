import { isListingDimmed } from '@/Components/Conversations/conversationListing'
import { LISTING_THUMB_SIZE } from '@/Components/Listings/listingImageSize'
import type { ConversationThumbnailProps } from '@/types/conversation'

export default function ConversationThumbnail({ listing, className }: ConversationThumbnailProps) {
    return (
        <div className={`relative shrink-0 overflow-hidden bg-sand ${className}`}>
            {listing.imageUrl && (
                <img
                    src={listing.imageUrl}
                    alt=""
                    width={LISTING_THUMB_SIZE.width}
                    height={LISTING_THUMB_SIZE.height}
                    loading="lazy"
                    decoding="async"
                    className={`absolute inset-0 size-full object-cover ${isListingDimmed(listing) ? 'opacity-50' : ''}`}
                />
            )}
        </div>
    )
}
