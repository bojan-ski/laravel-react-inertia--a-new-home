import { Link } from '@inertiajs/react'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import { formatPrice } from '@/Components/Listings/listingFormat'
import type { LatestHomeCardProps } from '@/types/home'

export default function LatestHomeCard({ listing, perMonth }: LatestHomeCardProps) {
    const price = `${formatPrice(listing.price, listing.currency)}${listing.listingType === 'rent' ? ` ${perMonth}` : ''}`

    return (
        <Link
            href={`/listings/${listing.slug}`}
            className="flex w-68 flex-none snap-start flex-col gap-3 lg:w-auto lg:gap-4"
        >
            <div className="aspect-3/4 overflow-hidden">
                {listing.imageUrl ? (
                    <img src={listing.imageUrl} alt={listing.title} loading="lazy" className="size-full object-cover" />
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
