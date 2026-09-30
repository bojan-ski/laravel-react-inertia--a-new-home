import { Link } from '@inertiajs/react'
import { formatDate, formatPrice } from '@/Components/Listings/listingFormat'
import Kicker from '@/Components/UI/Kicker'
import type { ListingDetailHeaderProps } from '@/types/listing'

export default function ListingDetailHeader({
    listing,
    translations,
    propertyLabel,
    showBack = true,
}: ListingDetailHeaderProps) {
    const typeLabel = listing.listingType === 'rent' ? translations.for_rent : translations.for_sale
    const address = [listing.addressLine, listing.city, listing.country, listing.postalCode].filter(Boolean).join(', ')
    const meta = [
        listing.yearBuilt !== null ? translations.built.replace(':year', String(listing.yearBuilt)) : null,
        listing.approvedAt ? translations.listed.replace(':date', formatDate(listing.approvedAt)) : null,
    ]
        .filter(Boolean)
        .join(' · ')

    return (
        <header className="flex flex-col gap-10">
            {showBack && (
                <Link href="/listings" className="hidden self-start text-sm text-ink-muted lg:inline-flex">
                    <span aria-hidden="true">←&nbsp;</span>
                    {translations.back}
                </Link>
            )}

            <div className="flex flex-col gap-2.5 lg:grid lg:grid-cols-[minmax(0,8fr)_minmax(0,4fr)] lg:items-end lg:gap-16">
                <div className="flex min-w-0 flex-col gap-2.5 lg:gap-4">
                    <Kicker size="sm" className="lg:text-xs">
                        {typeLabel} · {propertyLabel}
                    </Kicker>
                    <h1 className="text-[40px] leading-none font-extralight tracking-[-0.04em] wrap-break-word md:text-[56px] md:leading-[0.95] lg:text-[72px] lg:tracking-[-0.045em] xl:text-display-l">
                        {listing.title}
                    </h1>
                    <p className="text-sm text-ink-muted lg:text-[17px]">{address}</p>
                </div>

                <div className="mt-2 flex flex-col lg:mt-0 lg:items-end lg:gap-1.5 lg:pb-1.5 lg:text-right">
                    <p className="text-[30px] font-light tracking-[-0.02em] lg:text-[52px] lg:font-airy lg:tracking-[-0.03em]">
                        {formatPrice(listing.price, listing.currency)}
                        {listing.listingType === 'rent' && (
                            <span className="text-[15px] font-normal tracking-normal"> {translations.per_month}</span>
                        )}
                    </p>
                    {meta && <p className="hidden text-sm text-ink-muted lg:block">{meta}</p>}
                </div>
            </div>
        </header>
    )
}
