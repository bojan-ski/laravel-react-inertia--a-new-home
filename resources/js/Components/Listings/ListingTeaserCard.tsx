import { Link } from '@inertiajs/react'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import { formatPrice } from '@/Components/Listings/listingFormat'
import { photoFadeProps } from '@/Components/UI/photoFade'
import type { ListingTeaserCardProps } from '@/types/listing'

const aspectFrames = {
    '3/4': { className: 'aspect-3/4', width: 900, height: 1200 },
    '4/3': { className: 'aspect-4/3', width: 640, height: 480 },
}

export default function ListingTeaserCard({ listing, perMonth, aspect, mobileWidth }: ListingTeaserCardProps) {
    const price = `${formatPrice(listing.price, listing.currency)}${listing.listingType === 'rent' ? ` ${perMonth}` : ''}`
    const frame = aspectFrames[aspect]

    return (
        <Link
            href={`/listings/${listing.slug}`}
            className={`flex flex-none snap-start flex-col gap-3 lg:w-auto lg:gap-4 ${mobileWidth}`}
        >
            <div className={`${frame.className} overflow-hidden`}>
                {listing.imageUrl ? (
                    <img
                        src={listing.imageUrl}
                        alt={listing.title}
                        width={frame.width}
                        height={frame.height}
                        loading="lazy"
                        decoding="async"
                        {...photoFadeProps}
                        className="size-full object-cover"
                    />
                ) : (
                    <ListingImagePlaceholder />
                )}
            </div>

            <div className="hidden justify-between gap-4 text-[15px] lg:flex">
                <span className="font-medium">{listing.title}</span>
                <span className="shrink-0">{price}</span>
            </div>
            <span className="-mt-2.5 hidden text-sm text-ink/62 lg:block">
                {listing.city}, {listing.country}
            </span>

            <span className="text-[15px] font-medium lg:hidden">{listing.title}</span>
            <span className="-mt-1.5 text-sm text-ink/65 lg:hidden">
                {listing.city} · {price}
            </span>
        </Link>
    )
}
