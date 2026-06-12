import { MouseEvent } from 'react'
import { Link, router } from '@inertiajs/react'
import { Bath, BedDouble, Heart, MapPin, Maximize, User } from 'lucide-react'
import { formatArea, formatPrice } from '@/Components/Listings/listingFormat'
import type { ListingCardProps } from '@/types/listing'

export default function ListingCard({ listing, translations, propertyTypeLabels }: ListingCardProps) {
    const isRental = listing.listingType === 'rent'
    const typeLabel = isRental ? translations.for_rent : translations.for_sale
    const propertyLabel = propertyTypeLabels[listing.propertyType] ?? listing.propertyType

    function handleHeartClick(event: MouseEvent) {
        event.preventDefault()
        router.post(`/listings/${listing.id}/bookmark`, {}, { preserveScroll: true })
    }

    return (
        <Link
            href={`/listings/${listing.slug}`}
            className="group flex flex-col overflow-hidden rounded-xl border border-border bg-surface shadow-sm transition-shadow hover:shadow-md"
        >
            <div className="relative aspect-[4/3] overflow-hidden bg-surface-muted">
                {listing.imageUrl && (
                    <img
                        src={listing.imageUrl}
                        alt={listing.title}
                        className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                )}

                <div className="absolute left-3 top-3 flex gap-2">
                    <span className="rounded-md bg-surface-dark px-2.5 py-1 text-xs font-medium text-text-inverse">
                        {typeLabel}
                    </span>
                    <span className="rounded-md border border-border bg-surface px-2.5 py-1 text-xs font-medium text-text">
                        {propertyLabel}
                    </span>
                </div>

                <button
                    type="button"
                    onClick={handleHeartClick}
                    aria-label={listing.isBookmarked ? translations.saved : translations.save}
                    aria-pressed={listing.isBookmarked}
                    className={`absolute right-3 top-3 flex size-8 items-center justify-center rounded-full bg-white/80 transition-colors ${
                        listing.isBookmarked ? 'text-status-rejected' : 'text-text-soft hover:text-text'
                    }`}
                >
                    <Heart className={`size-4 ${listing.isBookmarked ? 'fill-current' : ''}`} />
                </button>
            </div>

            <div className="flex flex-1 flex-col gap-2 p-4">
                <div className="flex items-center gap-1 text-xs text-text-muted">
                    <MapPin className="size-3.5" />
                    <span className="truncate">
                        {listing.city}, {listing.country}
                    </span>
                </div>

                <h3 className="line-clamp-2 font-semibold text-text">{listing.title}</h3>

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-text-muted">
                    {listing.bedrooms !== null && (
                        <span className="flex items-center gap-1" aria-label={`${listing.bedrooms} ${translations.beds}`}>
                            <BedDouble className="size-4" />
                            {listing.bedrooms}
                        </span>
                    )}
                    {listing.bathrooms !== null && (
                        <span className="flex items-center gap-1" aria-label={`${listing.bathrooms} ${translations.baths}`}>
                            <Bath className="size-4" />
                            {listing.bathrooms}
                        </span>
                    )}
                    {listing.areaValue !== null && (
                        <span className="flex items-center gap-1">
                            <Maximize className="size-4" />
                            {formatArea(listing.areaValue, listing.areaUnit)}
                        </span>
                    )}
                </div>

                <div className="mt-auto flex items-end justify-between gap-2 pt-2">
                    <p className="font-bold text-text">
                        {formatPrice(listing.price, listing.currency)}
                        {isRental && <span className="text-sm font-normal text-text-muted">{translations.per_month}</span>}
                    </p>

                    {listing.ownerName && (
                        <div className="flex min-w-0 items-center gap-1 text-xs text-text-muted">
                            <User className="size-3.5 shrink-0" />
                            <span className="truncate">{listing.ownerName}</span>
                        </div>
                    )}
                </div>
            </div>
        </Link>
    )
}
