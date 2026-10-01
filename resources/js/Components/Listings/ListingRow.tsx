import { Link } from '@inertiajs/react'
import { countLabel, formatArea, formatPrice } from '@/Components/Listings/listingFormat'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import { LISTING_THUMB_SIZE } from '@/Components/Listings/listingImageSize'
import ListingSaveButton from '@/Components/Listings/ListingSaveButton'
import Kicker from '@/Components/UI/Kicker'
import type { ListingRowProps } from '@/types/listing'

export default function ListingRow({ listing, translations, propertyTypeLabels }: ListingRowProps) {
    const isRental = listing.listingType === 'rent'
    const kicker = `${isRental ? translations.for_rent : translations.for_sale} · ${
        propertyTypeLabels[listing.propertyType] ?? listing.propertyType
    }`
    const bedrooms =
        listing.bedrooms !== null ? countLabel(listing.bedrooms, translations.bedroom, translations.bedrooms) : null
    const bathroomsAndArea = [
        listing.bathrooms !== null ? countLabel(listing.bathrooms, translations.bathroom, translations.bathrooms) : null,
        listing.areaValue !== null ? formatArea(listing.areaValue, listing.areaUnit) : null,
    ]
        .filter(Boolean)
        .join(' · ')

    return (
        <article className="relative grid grid-cols-[240px_minmax(0,1fr)_180px_150px] items-center gap-8 border-b border-rule py-7 transition-colors duration-150 hover:bg-sand/60 xl:grid-cols-[320px_minmax(0,1fr)_220px_180px] xl:gap-12">
            <div className="aspect-3/2 overflow-hidden">
                {listing.imageUrl ? (
                    <img
                        src={listing.imageUrl}
                        alt={listing.title}
                        width={LISTING_THUMB_SIZE.width}
                        height={LISTING_THUMB_SIZE.height}
                        loading="lazy"
                        decoding="async"
                        className="size-full object-cover"
                    />
                ) : (
                    <ListingImagePlaceholder />
                )}
            </div>

            <div className="flex min-w-0 flex-col gap-2.5">
                <Kicker>{kicker}</Kicker>
                <h2 className="text-[28px]/[1.1] font-light tracking-[-0.02em] xl:text-[32px]/[1.1]">
                    <Link href={`/listings/${listing.slug}`} className="after:absolute after:inset-0">
                        {listing.title}
                    </Link>
                </h2>
                <p className="text-[15px] text-ink-muted">
                    {listing.city}, {listing.country}
                </p>
            </div>

            <div className="text-[15px]/[1.8] font-book text-ink/75">
                {bedrooms && <p>{bedrooms}</p>}
                {bathroomsAndArea && <p>{bathroomsAndArea}</p>}
            </div>

            <div className="flex flex-col items-end gap-3 text-right">
                <p className="text-2xl tracking-[-0.01em]">
                    {formatPrice(listing.price, listing.currency)}
                    {isRental && <span className="text-sm"> {translations.per_month}</span>}
                </p>
                <ListingSaveButton listing={listing} translations={translations} className="-my-3" />
            </div>
        </article>
    )
}
