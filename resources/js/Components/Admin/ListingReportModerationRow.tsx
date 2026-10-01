import { Link } from '@inertiajs/react'
import ListingReportActions from '@/Components/Admin/ListingReportActions'
import ListingReportList from '@/Components/Admin/ListingReportList'
import { notLiveNoticeId } from '@/Components/Admin/listingReportIds'
import ListingImagePlaceholder from '@/Components/Listings/ListingImagePlaceholder'
import { countLabel, formatPrice } from '@/Components/Listings/listingFormat'
import Button from '@/Components/UI/Button'
import FlashBanner from '@/Components/UI/FlashBanner'
import type { ListingReportModerationRowProps } from '@/types/listing-report'

export default function ListingReportModerationRow({
    listing,
    translations,
    reasonLabels,
}: ListingReportModerationRowProps) {
    const reviewUrl = `/admin/listings/${listing.id}`
    const price = `${formatPrice(listing.price, listing.currency)}${listing.listingType === 'rent' ? translations.per_month : ''}`

    const summary = [
        [listing.city, listing.country].filter(Boolean).join(', '),
        translations.listing_types[listing.listingType],
        price,
    ]
        .filter(Boolean)
        .join(' · ')

    const owner = translations.owner
        .replace(':name', listing.ownerName ?? '')
        .replace(':email', listing.ownerEmail ?? '')

    return (
        <li className="flex flex-col gap-6 border-t border-rule pt-8 lg:grid lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-12">
            <div className="flex min-w-0 flex-col gap-3.5">
                <div className="relative aspect-4/3 overflow-hidden bg-sand">
                    {listing.imageUrl ? (
                        <img
                            src={listing.imageUrl}
                            alt=""
                            className="absolute inset-0 size-full object-cover"
                            loading="lazy"
                        />
                    ) : (
                        <ListingImagePlaceholder className="absolute inset-0" />
                    )}
                </div>
                <Link
                    href={reviewUrl}
                    className="text-[22px] leading-tight font-light tracking-[-0.015em] wrap-break-word transition-colors duration-150 hover:text-forest lg:text-[26px]"
                >
                    {listing.title}
                </Link>
                <p className="text-sm">{summary}</p>
                <p className="text-[13px] wrap-break-word text-ink-muted">{owner}</p>
                <Button
                    variant="strong-link"
                    href={reviewUrl}
                    className="self-start text-sm"
                    trailing={<span aria-hidden="true">→</span>}
                >
                    {translations.view_listing}
                </Button>
            </div>

            <div className="flex min-w-0 flex-col gap-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-baseline gap-3">
                        <h2 className="text-[26px] leading-tight font-airy tracking-[-0.02em] lg:text-[32px]">
                            {translations.panel_heading}
                        </h2>
                        <span className="text-sm text-ink-muted">
                            {countLabel(
                                listing.openReportsCount,
                                translations.reports_count_one,
                                translations.reports_count_other,
                            )}
                        </span>
                    </div>
                    <div className="flex gap-2.5">
                        <ListingReportActions listing={listing} translations={translations} />
                    </div>
                </div>

                <ListingReportList reports={listing.reports} translations={translations} reasonLabels={reasonLabels} />

                {!listing.canTakeDown && (
                    <div id={notLiveNoticeId(listing.id)}>
                        <FlashBanner message={translations.not_approved} variant="info" className="" />
                    </div>
                )}
            </div>
        </li>
    )
}
