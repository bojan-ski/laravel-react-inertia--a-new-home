import { formatArea } from '@/Components/Listings/listingFormat'
import type { ListingFactsProps } from '@/types/listing'

const columnClasses = ['', 'grid-cols-1', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4']

export default function ListingFacts({ listing, translations }: ListingFactsProps) {
    const facts = [
        listing.bedrooms !== null && {
            key: 'bedrooms',
            value: String(listing.bedrooms),
            label: listing.bedrooms === 1 ? translations.bed : translations.beds,
        },
        listing.bathrooms !== null && {
            key: 'bathrooms',
            value: String(listing.bathrooms),
            label: listing.bathrooms === 1 ? translations.bath : translations.baths,
        },
        listing.areaValue !== null && {
            key: 'area',
            value: formatArea(listing.areaValue, null),
            label: listing.areaUnit ?? '',
        },
        listing.yearBuilt !== null && {
            key: 'year',
            value: String(listing.yearBuilt),
            label: translations.detail_year_built,
        },
    ].filter((fact) => fact !== false)

    if (facts.length === 0) {
        return null
    }

    return (
        <dl className={`grid border-y border-rule ${columnClasses[facts.length]}`}>
            {facts.map((fact) => (
                <div key={fact.key} className="flex flex-col-reverse justify-end gap-0.5 py-4 lg:gap-1.5 lg:py-7">
                    <dt className="text-[10px] tracking-widest text-ink/62 uppercase lg:text-xs lg:tracking-[0.12em]">
                        {fact.label}
                    </dt>
                    <dd className="text-2xl font-airy lg:text-5xl lg:font-extralight lg:tracking-[-0.03em]">
                        {fact.value}
                    </dd>
                </div>
            ))}
        </dl>
    )
}
