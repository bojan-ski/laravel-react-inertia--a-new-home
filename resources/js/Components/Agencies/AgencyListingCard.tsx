import { Link } from '@inertiajs/react'
import { Heart } from 'lucide-react'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import { LISTING_THUMB_SIZE } from '@/Components/Listings/listingImageSize'
import { formatPrice } from '@/Components/Listings/listingFormat'
import { useBookmarkToggle } from '@/Components/Listings/useListingActions'
import Kicker from '@/Components/UI/Kicker'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { AgencyListingCardProps } from '@/types/agency'

export default function AgencyListingCard({ listing, translations, propertyTypeLabels }: AgencyListingCardProps) {
    const bookmark = useBookmarkToggle(listing.id)
    const actionLabel = useActionLabel()
    const isRental = listing.listingType === 'rent'
    const typeLabel = isRental ? translations.for_rent : translations.for_sale
    const propertyLabel = propertyTypeLabels[listing.propertyType] ?? listing.propertyType

    return (
        <article className="relative flex flex-col gap-2 border-b border-rule py-5 md:gap-3.5 md:border-b-0 md:py-0">
            <div className="relative aspect-3/2 overflow-hidden bg-sand md:aspect-4/3">
                {listing.imageUrl ? (
                    <img
                        src={listing.imageUrl}
                        alt=""
                        width={LISTING_THUMB_SIZE.width}
                        height={LISTING_THUMB_SIZE.height}
                        loading="lazy"
                        decoding="async"
                        className="absolute inset-0 size-full object-cover"
                    />
                ) : (
                    <ListingImagePlaceholder />
                )}

                {listing.canBookmark && (
                    <button
                        type="button"
                        onClick={bookmark.toggle}
                        aria-disabled={bookmark.processing || undefined}
                        aria-pressed={listing.isBookmarked}
                        aria-label={actionLabel(listing.isBookmarked ? translations.saved : translations.save, listing.title)}
                        className="absolute top-0 right-0 z-10 flex size-11 items-center justify-center bg-paper transition-colors duration-150 hover:bg-sand aria-disabled:opacity-45"
                    >
                        <Heart
                            className={`size-4.5 ${listing.isBookmarked ? 'fill-forest stroke-forest' : 'stroke-ink'}`}
                            strokeWidth={1.25}
                            aria-hidden="true"
                        />
                    </button>
                )}
            </div>

            <Kicker size="sm" className="mt-1.5 md:mt-0">
                {typeLabel} · {propertyLabel}
            </Kicker>

            <div className="flex justify-between gap-3 text-[15px] md:-mt-1.5 md:gap-4 md:text-base">
                <h3 className="min-w-0 font-medium wrap-break-word">
                    <Link
                        href={`/listings/${listing.slug}`}
                        className="transition-colors duration-150 after:absolute after:inset-0 hover:text-forest"
                    >
                        {listing.title}
                    </Link>
                </h3>
                <span className="shrink-0">
                    {formatPrice(listing.price, listing.currency)}
                    {isRental && ` ${translations.per_month}`}
                </span>
            </div>

            <p className="text-[13px] text-ink/62 md:-mt-2 md:text-sm">
                {listing.city}, {listing.country}
            </p>
        </article>
    )
}
