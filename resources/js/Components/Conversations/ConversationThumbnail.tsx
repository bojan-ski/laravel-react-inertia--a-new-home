import { isListingDimmed } from '@/Components/Conversations/conversationListing'
import type { ConversationThumbnailProps } from '@/types/conversation'

export default function ConversationThumbnail({ listing, className }: ConversationThumbnailProps) {
    return (
        <div className={`relative shrink-0 overflow-hidden bg-sand ${className}`}>
            {listing.imageUrl && (
                <img
                    src={listing.imageUrl}
                    alt=""
                    className={`absolute inset-0 size-full object-cover ${isListingDimmed(listing) ? 'opacity-50' : ''}`}
                />
            )}
        </div>
    )
}
