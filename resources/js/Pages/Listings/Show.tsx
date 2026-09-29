import { Head, Link } from '@inertiajs/react'
import { ChevronLeft, MapPin } from 'lucide-react'
import ListingAmenities from '@/Components/Listings/ListingAmenities'
import ListingDetailRow from '@/Components/Listings/ListingDetailRow'
import ListingFacts from '@/Components/Listings/ListingFacts'
import ListingGallery from '@/Components/Listings/ListingGallery'
import ListingSidebar from '@/Components/Listings/ListingSidebar'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { ListingShowPageProps } from '@/types/listing'

export default function Show({ listing, isBookmarked, canBookmark, canStartChat, translations }: ListingShowPageProps) {
    const show = translations.show
    const propertyLabel = translations.filters.property_types[listing.propertyType] ?? listing.propertyType
    const typeLabel = listing.listingType === 'rent' ? show.for_rent : show.for_sale

    const address = [listing.addressLine, listing.city, listing.country, listing.postalCode]
        .filter(Boolean)
        .join(', ')

    return (
        <ListingsLayout>
            <Head title={listing.title} />

            <div className="mx-auto max-w-7xl px-6 py-8">
                <Link
                    href="/listings"
                    className="inline-flex items-center gap-1 text-sm text-text-muted transition-colors hover:text-text"
                >
                    <ChevronLeft className="size-4" />
                    {show.back}
                </Link>

                <div className="mt-6 flex flex-col gap-3">
                    <div className="flex flex-wrap gap-2">
                        <span className="rounded-md bg-surface-dark px-2.5 py-1 text-xs font-medium text-text-inverse">
                            {typeLabel}
                        </span>
                        <span className="rounded-md border border-border bg-surface px-2.5 py-1 text-xs font-medium text-text">
                            {propertyLabel}
                        </span>
                    </div>

                    <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">{listing.title}</h1>

                    <div className="flex items-center gap-1.5 text-text-muted">
                        <MapPin className="size-4" />
                        <span>
                            {listing.city}, {listing.country}
                        </span>
                    </div>
                </div>

                {listing.images.length > 0 && (
                    <div className="mt-8">
                        <ListingGallery images={listing.images} title={listing.title} />
                    </div>
                )}

                <div className="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div className="flex flex-col gap-10 lg:col-span-2">
                        <ListingFacts listing={listing} translations={show} />

                        <section>
                            <h2 className="font-display text-xl font-semibold text-text">
                                {show.description_heading}
                            </h2>
                            <p className="mt-4 max-w-prose whitespace-pre-line leading-8 text-text-soft">
                                {listing.description}
                            </p>
                        </section>

                        <ListingAmenities
                            amenities={listing.amenities}
                            labels={translations.amenities}
                            heading={show.amenities_heading}
                        />

                        <section>
                            <h2 className="font-display text-xl font-semibold text-text">{show.details_heading}</h2>
                            <dl className="mt-4 flex flex-col gap-4">
                                <ListingDetailRow label={show.detail_address} value={address} />
                                <ListingDetailRow label={show.detail_property_type} value={propertyLabel} />
                                <ListingDetailRow label={show.detail_listing_type} value={typeLabel} />
                                {listing.yearBuilt !== null && (
                                    <ListingDetailRow label={show.detail_year_built} value={String(listing.yearBuilt)} />
                                )}
                                {listing.approvedAt && (
                                    <ListingDetailRow label={show.detail_listed} value={listing.approvedAt} />
                                )}
                            </dl>
                        </section>
                    </div>

                    <div className="lg:col-span-1">
                        <ListingSidebar
                            listing={listing}
                            isBookmarked={isBookmarked}
                            canBookmark={canBookmark}
                            canStartChat={canStartChat}
                            translations={show}
                        />
                    </div>
                </div>
            </div>
        </ListingsLayout>
    )
}
