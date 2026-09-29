import { Check } from 'lucide-react'
import type { ListingAmenitiesProps } from '@/types/listing'

export default function ListingAmenities({ amenities, labels, heading }: ListingAmenitiesProps) {
    if (amenities.length === 0) {
        return null
    }

    return (
        <section>
            <h2 className="font-display text-xl font-semibold text-text">{heading}</h2>
            <ul className="mt-4 grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                {amenities.map((amenity) => (
                    <li key={amenity} className="flex items-center gap-3 text-text-soft">
                        <Check className="size-5 shrink-0 text-brand" aria-hidden="true" />
                        <span>{labels[amenity] ?? amenity}</span>
                    </li>
                ))}
            </ul>
        </section>
    )
}
