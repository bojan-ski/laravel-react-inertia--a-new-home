import { useState } from 'react'
import { Link, router } from '@inertiajs/react'
import { Heart } from 'lucide-react'
import { formatPrice } from '@/Components/Listings/listingFormat'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import StatusBadge from '@/Components/UI/StatusBadge'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { BookmarkCardProps } from '@/types/bookmark'

export default function BookmarkCard({ listing, variant, closedLabel, perMonth, removeLabel }: BookmarkCardProps) {
    const [processing, setProcessing] = useState(false)
    const actionLabel = useActionLabel()
    const isClosed = variant === 'closed'

    function handleRemove() {
        router.post(
            `/listings/${listing.id}/bookmark`,
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        )
    }

    return (
        <article className="relative flex flex-col gap-2.5 border-b border-rule py-5 sm:gap-3.5 sm:border-b-0 sm:py-0">
            <div className={`relative overflow-hidden bg-sand ${isClosed ? 'aspect-3/2' : 'aspect-4/3'}`}>
                {listing.imageUrl ? (
                    <img
                        src={listing.imageUrl}
                        alt=""
                        className={`absolute inset-0 size-full object-cover ${isClosed ? 'opacity-45' : ''}`}
                    />
                ) : (
                    <ListingImagePlaceholder />
                )}

                {isClosed && closedLabel && (
                    <span className="pointer-events-none absolute top-0 left-0">
                        <StatusBadge status="sold" label={closedLabel} />
                    </span>
                )}

                {listing.canBookmark && (
                    <button
                        type="button"
                        onClick={handleRemove}
                        disabled={processing}
                        aria-label={actionLabel(removeLabel, listing.title)}
                        className="absolute top-0 right-0 z-10 flex size-11 items-center justify-center bg-paper transition-colors duration-150 hover:bg-sand"
                    >
                        <Heart className="size-4.5 fill-forest stroke-forest" strokeWidth={1.25} aria-hidden="true" />
                    </button>
                )}
            </div>

            <div className="flex justify-between gap-4 text-[15px]">
                {isClosed ? (
                    <h3 className="font-medium text-ink-muted">{listing.title}</h3>
                ) : (
                    <>
                        <h2 className="font-medium">
                            <Link
                                href={`/listings/${listing.slug}`}
                                className="after:absolute after:inset-0 hover:text-forest"
                            >
                                {listing.title}
                            </Link>
                        </h2>
                        <span className="shrink-0">
                            {formatPrice(listing.price, listing.currency)}
                            {listing.listingType === 'rent' && ` ${perMonth}`}
                        </span>
                    </>
                )}
            </div>

            <p className="-mt-2 text-sm text-ink-muted">
                {listing.city}, {listing.country}
            </p>
        </article>
    )
}
