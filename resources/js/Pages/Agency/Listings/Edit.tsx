import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import ImageUploader from '@/Components/Listings/ImageUploader'
import ListingApprovedNotice from '@/Components/Listings/ListingApprovedNotice'
import ListingForm from '@/Components/Listings/ListingForm'
import ListingFormHeader from '@/Components/Listings/ListingFormHeader'
import FlashBanner from '@/Components/UI/FlashBanner'
import PublicLayout from '@/Layouts/PublicLayout'
import type { EditAgencyListingPageProps, ListingFormData } from '@/types/listing'
import type { SharedProps } from '@/types/shared'

export default function Edit({
    listing,
    translations,
    propertyTypeLabels,
    amenityLabels,
    countryOptions,
    maxImages,
}: EditAgencyListingPageProps) {
    const { flash } = usePage<SharedProps>().props
    const isApproved = listing.status === 'approved'

    const { data, setData, put, processing, errors } = useForm<ListingFormData>({
        title: listing.title,
        listing_type: listing.listingType,
        property_type: listing.propertyType,
        price: listing.price,
        description: listing.description,
        country: countryOptions.includes(listing.country) ? listing.country : '',
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

    const beforeSections =
        isApproved || flash.error ? (
            <>
                {isApproved && <ListingApprovedNotice message={translations.approved_notice} />}
            </>
        ) : null

    return (
        <PublicLayout back={{ href: '/agency', label: translations.back }} footer={false} contained>
            <Head title={translations.title} />

            <ListingFormHeader
                title={translations.title}
                subtitle={translations.subtitle}
                back={translations.back}
                backHref="/agency"
            />

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
                countryOptions={countryOptions}
                beforeSections={beforeSections}
                imagesNote={listing.images.length === 0 ? translations.images.helper_empty : translations.images.helper}
                imagesSection={
                    <ImageUploader
                        baseUrl={`/agency/listings/${listing.id}/images`}
                        images={listing.images}
                        listingTitle={listing.title}
                        maxImages={maxImages}
                        translations={translations.images}
                    />
                }
                barNote={isApproved ? translations.bar_note_approved : translations.submit_note}
                contactNote={translations.contact_inheritance_note}
                hideContactSection
                cancelHref="/agency"
            />
        </PublicLayout>
    )
}
