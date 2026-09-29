import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { ChevronLeft, Info } from 'lucide-react'
import type { FormEvent } from 'react'
import ImageUploader from '@/Components/Listings/ImageUploader'
import ListingForm from '@/Components/Listings/ListingForm'
import FlashBanner from '@/Components/UI/FlashBanner'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { EditAgencyListingPageProps, ListingFormData } from '@/types/listing'
import type { SharedProps } from '@/types/shared'

export default function Edit({ listing, translations, propertyTypeLabels, amenityLabels }: EditAgencyListingPageProps) {
    const { flash } = usePage<SharedProps>().props

    const { data, setData, put, processing, errors } = useForm<ListingFormData>({
        title: listing.title,
        listing_type: listing.listingType,
        property_type: listing.propertyType,
        price: listing.price,
        description: listing.description,
        country: listing.country,
        city: listing.city,
        address_line: listing.addressLine ?? '',
        postal_code: listing.postalCode ?? '',
        bedrooms: listing.bedrooms?.toString() ?? '',
        bathrooms: listing.bathrooms?.toString() ?? '',
        area_value: listing.areaValue ?? '',
        year_built: listing.yearBuilt?.toString() ?? '',
        amenities: listing.amenities,
        contact_name: '',
        contact_email: '',
        contact_phone: '',
    })

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault()
        put(`/agency/listings/${listing.id}`)
    }

    const headerSlot = (
        <div className="space-y-4">
            {listing.status === 'approved' && (
                <div className="flex items-start gap-3 rounded-lg border border-accent bg-accent-soft px-5 py-4 text-text">
                    <Info className="mt-0.5 size-5 shrink-0 text-accent" />
                    <p className="text-sm">{translations.approved_notice}</p>
                </div>
            )}
            <p className="text-sm text-text-soft">{translations.contact_inheritance_note}</p>
        </div>
    )

    return (
        <ListingsLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-10 sm:flex-row sm:items-start sm:justify-between md:py-14">
                    <div>
                        <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                            {translations.title}
                        </h1>
                        <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                    </div>

                    <Link
                        href="/agency"
                        className="inline-flex items-center gap-1.5 text-sm text-text-soft transition-colors hover:text-text"
                    >
                        <ChevronLeft className="size-4" />
                        {translations.back}
                    </Link>
                </div>
            </div>

            <div className="mx-auto max-w-3xl px-6 py-10">
                {flash.error && <FlashBanner message={flash.error} variant="error" className="mb-8" />}

                <ListingForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    submitLabel={translations.submit}
                    onSubmit={submit}
                    translations={translations}
                    propertyTypeLabels={propertyTypeLabels}
                    amenityLabels={amenityLabels}
                    headerSlot={headerSlot}
                    hideContactSection
                    cancelHref="/agency"
                />

                <div className="mt-12 border-t border-border pt-10">
                    <ImageUploader
                        baseUrl={`/agency/listings/${listing.id}/images`}
                        images={listing.images}
                        translations={translations.images}
                        canManage
                    />
                </div>
            </div>
        </ListingsLayout>
    )
}
