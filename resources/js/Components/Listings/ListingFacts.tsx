import { Bath, BedDouble, CalendarDays, Maximize } from 'lucide-react'
import { formatArea } from '@/Components/Listings/listingFormat'
import type { ListingFactsProps } from '@/types/listing'

export default function ListingFacts({ listing, translations }: ListingFactsProps) {
    const hasFacts =
        listing.bedrooms !== null ||
        listing.bathrooms !== null ||
        listing.areaValue !== null ||
        listing.yearBuilt !== null

    if (!hasFacts) {
        return null
    }

    return (
        <div className="flex flex-wrap gap-x-10 gap-y-4 border-y border-border py-6">
            {listing.bedrooms !== null && (
                <div className="flex items-center gap-3">
                    <BedDouble className="size-5 text-text-muted" />
                    <span className="text-text">
                        <span className="font-semibold">{listing.bedrooms}</span>{' '}
                        {listing.bedrooms === 1 ? translations.bed : translations.beds}
                    </span>
                </div>
            )}

            {listing.bathrooms !== null && (
                <div className="flex items-center gap-3">
                    <Bath className="size-5 text-text-muted" />
                    <span className="text-text">
                        <span className="font-semibold">{listing.bathrooms}</span>{' '}
                        {listing.bathrooms === 1 ? translations.bath : translations.baths}
                    </span>
                </div>
            )}

            {listing.areaValue !== null && (
                <div className="flex items-center gap-3">
                    <Maximize className="size-5 text-text-muted" />
                    <span className="font-semibold text-text">{formatArea(listing.areaValue, listing.areaUnit)}</span>
                </div>
            )}

            {listing.yearBuilt !== null && (
                <div className="flex items-center gap-3">
                    <CalendarDays className="size-5 text-text-muted" />
                    <span className="font-semibold text-text">
                        {translations.built.replace(':year', String(listing.yearBuilt))}
                    </span>
                </div>
            )}
        </div>
    )
}
