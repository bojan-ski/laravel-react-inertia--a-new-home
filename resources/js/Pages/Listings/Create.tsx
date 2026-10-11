import { Head, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import ListingForm from '@/Components/Listings/ListingForm'
import ListingFormHeader from '@/Components/Listings/ListingFormHeader'
import NewListingImagePicker, { photoErrorKeys } from '@/Components/Listings/NewListingImagePicker'
import PublicLayout from '@/Layouts/PublicLayout'
import type { CreateListingFormData, CreateListingPageProps } from '@/types/listing'

export default function Create({
    translations,
    propertyTypeLabels,
    amenityLabels,
    countryOptions,
    maxImages,
}: CreateListingPageProps) {
    const { data, setData, post, processing, errors, clearErrors } = useForm<CreateListingFormData>({
        title: '',
        listing_type: 'sale',
        property_type: 'apartment',
        price: '',
        description: '',
        country: '',
        city: '',
        address_line: '',
        postal_code: '',
        bedrooms: '',
        bathrooms: '',
        area_value: '',
        year_built: '',
        amenities: [],
        contact_name: '',
        contact_email: '',
        contact_phone: '',
        images: [],
    })

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault()
        post('/my-listings', { forceFormData: true })
    }

    return (
        <PublicLayout back={{ href: '/profile', label: translations.back }} footer={false} contained>
            <Head title={translations.title} />

            <ListingFormHeader
                title={translations.title}
                subtitle={translations.subtitle}
                back={translations.back}
                backHref="/profile"
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
                imagesNote={translations.new_images.helper.replace(':max', String(maxImages))}
                imagesSection={
                    <NewListingImagePicker
                        files={data.images}
                        onChange={(files) => {
                            setData('images', files)
                            const staleErrors = photoErrorKeys(errors)
                            if (staleErrors.length > 0) {
                                clearErrors(...staleErrors)
                            }
                        }}
                        errors={errors}
                        maxImages={maxImages}
                        translations={translations.new_images}
                    />
                }
                barNote={translations.submit_note}
            />
        </PublicLayout>
    )
}
