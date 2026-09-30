import ListingAmenities from '@/Components/Listings/ListingAmenities'
import ListingDescription from '@/Components/Listings/ListingDescription'
import ListingDetailBlock from '@/Components/Listings/ListingDetailBlock'
import ListingDetailRow from '@/Components/Listings/ListingDetailRow'
import { formatDate } from '@/Components/Listings/listingFormat'
import type { ListingDetailSectionsProps } from '@/types/listing'

export default function ListingDetailSections({
    listing,
    translations,
    propertyLabel,
    amenityLabels,
    mortgage,
    sidebar,
    footer,
}: ListingDetailSectionsProps) {
    const typeLabel = listing.listingType === 'rent' ? translations.for_rent : translations.for_sale
    const address = [listing.addressLine, listing.city, listing.country, listing.postalCode].filter(Boolean).join(', ')

    return (
        <div className="@container">
            <div className="grid gap-y-10 @4xl:grid-cols-[minmax(0,1fr)_320px] @4xl:gap-x-16 @6xl:grid-cols-[minmax(0,7fr)_minmax(0,1fr)_minmax(0,4fr)] @6xl:gap-x-0">
                <div className="flex min-w-0 flex-col gap-10 @4xl:gap-16 @6xl:gap-22">
                    <ListingDetailBlock heading={translations.description_heading} headingClassName="@6xl:pt-2.5">
                        <ListingDescription description={listing.description} />
                    </ListingDetailBlock>

                    {listing.amenities.length > 0 && (
                        <ListingDetailBlock heading={translations.amenities_heading}>
                            <ListingAmenities amenities={listing.amenities} labels={amenityLabels} />
                        </ListingDetailBlock>
                    )}

                    <ListingDetailBlock heading={translations.details_heading}>
                        <dl className="flex flex-col">
                            <ListingDetailRow label={translations.detail_address} value={address} />
                            <ListingDetailRow label={translations.detail_property_type} value={propertyLabel} />
                            <ListingDetailRow label={translations.detail_listing_type} value={typeLabel} />
                            {listing.yearBuilt !== null && (
                                <ListingDetailRow
                                    label={translations.detail_year_built}
                                    value={String(listing.yearBuilt)}
                                />
                            )}
                            {listing.approvedAt && (
                                <ListingDetailRow
                                    label={translations.detail_listed}
                                    value={formatDate(listing.approvedAt, 'long')}
                                />
                            )}
                        </dl>
                    </ListingDetailBlock>

                    {mortgage}
                    {footer}
                </div>

                {sidebar}
            </div>
        </div>
    )
}
