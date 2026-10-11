import { Link } from '@inertiajs/react'
import { formatArea, formatPrice } from '@/Components/Listings/listingFormat'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import { LISTING_THUMB_SIZE } from '@/Components/Listings/listingImageSize'
import ListingSaveButton from '@/Components/Listings/ListingSaveButton'
import Kicker from '@/Components/UI/Kicker'
import { photoFadeProps } from '@/Components/UI/photoFade'
import type { ListingRowProps } from '@/types/listing'

export default function ListingRowCard({ listing, translations, propertyTypeLabels }: ListingRowProps) {
    const isRental = listing.listingType === 'rent'
    const kicker = `${isRental ? translations.for_rent : translations.for_sale} · ${
        propertyTypeLabels[listing.propertyType] ?? listing.propertyType
    }`
    const facts = [
        listing.bedrooms !== null ? translations.bd.replace(':count', String(listing.bedrooms)) : null,
        listing.bathrooms !== null ? translations.ba.replace(':count', String(listing.bathrooms)) : null,
        listing.areaValue !== null ? formatArea(listing.areaValue, listing.areaUnit) : null,
    ]
        .filter(Boolean)
        .join(' · ')

    return (
        <article className="relative flex flex-col gap-2.5 border-b border-rule py-7">
            <div className="aspect-3/2 overflow-hidden">
                {listing.imageUrl ? (
                    <img
                        src={listing.imageUrl}
                        alt={listing.title}
                        width={LISTING_THUMB_SIZE.width}
                        height={LISTING_THUMB_SIZE.height}
                        loading="lazy"
                        decoding="async"
                        {...photoFadeProps}
                        className="size-full object-cover"
                    />
                ) : (
                    <ListingImagePlaceholder />
                )}
            </div>

            <Kicker size="sm" className="mt-2">
                {kicker}
            </Kicker>
            <h2 className="text-[26px]/[1.12] font-light tracking-[-0.02em]">
                <Link href={`/listings/${listing.slug}`} className="after:absolute after:inset-0">
                    {listing.title}
                </Link>
            </h2>
            <p className="text-sm text-ink-muted">
                {listing.city}, {listing.country}
            </p>

            <div className="mt-1 flex items-baseline justify-between gap-4">
                <p className="text-[13px] text-ink-muted">{facts}</p>
                <p className="shrink-0 text-xl">
                    {formatPrice(listing.price, listing.currency)}
                    {isRental && <span className="text-[13px]"> {translations.per_month}</span>}
                </p>
            </div>

            <ListingSaveButton listing={listing} translations={translations} className="-my-2 self-end" />
        </article>
    )
}
