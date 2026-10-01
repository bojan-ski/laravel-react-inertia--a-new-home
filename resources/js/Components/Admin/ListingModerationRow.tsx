import { Link } from '@inertiajs/react'
import ListingModerationActions from '@/Components/Admin/ListingModerationActions'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import { LISTING_THUMB_SIZE } from '@/Components/Listings/listingImageSize'
import { formatDate, formatPrice } from '@/Components/Listings/listingFormat'
import Button from '@/Components/UI/Button'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { AdminListingModerationRowProps } from '@/types/admin'

export default function ListingModerationRow({
    listing,
    translations,
    propertyTypeLabels,
}: AdminListingModerationRowProps) {
    const actionLabel = useActionLabel()
    const reviewUrl = `/admin/listings/${listing.id}`
    const price = `${formatPrice(listing.price, listing.currency)}${listing.listingType === 'rent' ? translations.per_month : ''}`

    const summary = [
        translations.listing_types[listing.listingType],
        propertyTypeLabels[listing.propertyType] ?? listing.propertyType,
        price,
    ].join(' · ')

    const owner = translations.owner
        .replace(':name', listing.ownerName ?? '')
        .replace(':email', listing.ownerEmail ?? '')

    return (
        <li className="grid grid-cols-[96px_minmax(0,1fr)] items-start gap-x-3.5 gap-y-1.5 border-b border-rule py-5 transition-colors duration-150 hover:bg-sand/55 md:grid-cols-[160px_minmax(0,1fr)_auto] md:items-center md:gap-8 md:py-6">
            <div className="relative aspect-3/2 overflow-hidden bg-sand">
                {listing.imageUrl ? (
                    <img
                        src={listing.imageUrl}
                        alt=""
                        width={LISTING_THUMB_SIZE.width}
                        height={LISTING_THUMB_SIZE.height}
                        className="absolute inset-0 size-full object-cover"
                        loading="lazy"
                        decoding="async"
                    />
                ) : (
                    <ListingImagePlaceholder className="absolute inset-0" />
                )}
            </div>

            <div className="contents md:flex md:min-w-0 md:flex-col md:gap-1.5">
                <Link
                    href={reviewUrl}
                    className="text-xl leading-tight font-light tracking-[-0.015em] wrap-break-word transition-colors duration-150 hover:text-forest md:text-2xl"
                >
                    {listing.title}
                </Link>
                <p className="col-span-2 mt-1.5 text-sm md:mt-0">{summary}</p>
                <p className="col-span-2 text-[13px] wrap-break-word text-ink-muted">{owner}</p>
                <p className="col-span-2 text-[13px] text-ink-muted">
                    {translations.submitted.replace(':date', formatDate(listing.createdAt))}
                </p>
            </div>

            <div className="col-span-2 mt-1.5 flex flex-wrap items-center gap-2.5 md:col-span-1 md:mt-0">
                <Button
                    variant="link"
                    href={reviewUrl}
                    aria-label={actionLabel(translations.view_listing, listing.title)}
                    className="mr-3"
                >
                    {translations.view_listing}
                </Button>
                <ListingModerationActions
                    listingId={listing.id}
                    listingTitle={listing.title}
                    translations={translations}
                />
            </div>
        </li>
    )
}
