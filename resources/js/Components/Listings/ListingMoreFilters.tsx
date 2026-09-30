import { AmenityChips, AreaFields, BathroomsSelect, CityField } from '@/Components/Listings/ListingFilterFields'
import type { ListingMoreFiltersProps } from '@/types/listing'

export default function ListingMoreFilters(props: ListingMoreFiltersProps) {
    return (
        <>
            <div className="grid grid-cols-2 gap-5">
                <CityField {...props} />
                <BathroomsSelect {...props} />
            </div>
            <AreaFields {...props} />
            <AmenityChips {...props} />
        </>
    )
}
