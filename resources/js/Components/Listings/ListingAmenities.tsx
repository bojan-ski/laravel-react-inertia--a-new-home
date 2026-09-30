import { Check } from 'lucide-react'
import type { ListingAmenitiesProps } from '@/types/listing'

export default function ListingAmenities({ amenities, labels }: ListingAmenitiesProps) {
    return (
        <ul className="grid grid-cols-2 gap-x-4 @4xl:gap-x-8">
            {amenities.map((amenity) => (
                <li
                    key={amenity}
                    className="flex items-center gap-2.5 border-b border-rule py-3 text-sm font-book @4xl:gap-3.5 @4xl:py-3.5 @4xl:text-base"
                >
                    <Check
                        className="size-4 shrink-0 text-forest @4xl:size-4.5"
                        strokeWidth={1.25}
                        aria-hidden="true"
                    />
                    <span className="min-w-0">{labels[amenity] ?? amenity}</span>
                </li>
            ))}
        </ul>
    )
}
