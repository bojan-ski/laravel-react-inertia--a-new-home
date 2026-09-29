import { Head, Link, useForm } from '@inertiajs/react'
import { ChevronLeft } from 'lucide-react'
import type { FormEvent } from 'react'
import ListingForm from '@/Components/Listings/ListingForm'
import NewListingImagePicker from '@/Components/Listings/NewListingImagePicker'
import PublicLayout from '@/Layouts/PublicLayout'
import type { CreateAgencyListingPageProps, CreateListingFormData } from '@/types/listing'

export default function Create({ translations, propertyTypeLabels, amenityLabels }: CreateAgencyListingPageProps) {
    const { data, setData, post, processing, errors } = useForm<CreateListingFormData>({
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
        post('/agency/listings', { forceFormData: true })
    }

    return (
        <PublicLayout back={{ href: '/agency', label: translations.back }} footer={false}>
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
                        className="hidden items-center gap-1.5 text-sm text-text-soft transition-colors hover:text-text lg:inline-flex"
                    >
                        <ChevronLeft className="size-4" />
                        {translations.back}
                    </Link>
                </div>
            </div>

            <div className="mx-auto max-w-3xl px-6 py-10">
                <p className="mb-8 text-sm text-text-soft">{translations.contact_inheritance_note}</p>

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
                    hideContactSection
                    cancelHref="/agency"
                    footerSlot={
                        <NewListingImagePicker
                            files={data.images}
                            onChange={(files) => setData('images', files)}
                            errors={errors}
                            translations={translations.new_images}
                        />
                    }
                />
            </div>
        </PublicLayout>
    )
}
