import ConversationThumbnail from '@/Components/Conversations/ConversationThumbnail'
import { conversationPriceLine } from '@/Components/Conversations/conversationListing'
import Button from '@/Components/UI/Button'
import type { ThreadHeaderProps } from '@/types/conversation'

export default function ThreadHeader({ conversation, translations }: ThreadHeaderProps) {
    const { listing, otherParticipant } = conversation
    const priceLine = conversationPriceLine(listing, translations)
    const withLine = [`${translations.thread.with} ${otherParticipant.name}`, priceLine].filter(Boolean).join(' · ')

    return (
        <>
            <div className="grid grid-cols-[48px_minmax(0,1fr)] items-center gap-2.5 border-b border-rule px-5 pb-3 lg:hidden">
                <ConversationThumbnail listing={listing} className="size-12" />
                <div className="flex min-w-0 flex-col">
                    <h1 className="truncate text-[15px]">{otherParticipant.name}</h1>
                    <p className="truncate text-xs text-ink-muted">{listing.title}</p>
                </div>
            </div>

            <div className="hidden grid-cols-[72px_minmax(0,1fr)_auto] items-center gap-5 border-b border-rule py-6 pr-10 pl-8 lg:grid xl:pr-16 xl:pl-12">
                <ConversationThumbnail listing={listing} className="h-13.5 w-18" />
                <div className="flex min-w-0 flex-col gap-1">
                    <h1 className="truncate text-xl font-light tracking-[-0.01em]">{listing.title}</h1>
                    <p className="truncate text-[13px] text-ink-muted">{withLine}</p>
                </div>
                {listing.isAvailable && listing.slug && (
                    <Button
                        href={`/listings/${listing.slug}`}
                        variant="link"
                        className="border-b border-current pb-0.5 text-ink! hover:text-forest!"
                    >
                        {translations.view_listing}
                    </Button>
                )}
            </div>
        </>
    )
}
