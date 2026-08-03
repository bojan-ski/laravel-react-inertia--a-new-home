import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { ChevronLeft, Info } from 'lucide-react'
import type { FormEvent } from 'react'
import ImageUploader from '@/Components/Listings/ImageUploader'
import ListingForm from '@/Components/Listings/ListingForm'
import FlashBanner from '@/Components/UI/FlashBanner'
import PublicLayout from '@/Layouts/PublicLayout'
import type { EditListingPageProps, ListingFormData } from '@/types/listing'
import type { SharedProps } from '@/types/shared'

export default function Edit({ listing, translations, propertyTypeLabels }: EditListingPageProps) {
    const { flash } = usePage<SharedProps>().props

    const { data, setData, put, processing, errors } = useForm<ListingFormData>({
        title: listing.title,
        listing_type: listing.listingType,
        property_type: listing.propertyType,
        price: listing.price,
        currency: listing.currency,
        description: listing.description,
        country: listing.country,
        city: listing.city,
        address_line: listing.addressLine ?? '',
        postal_code: listing.postalCode ?? '',
        bedrooms: listing.bedrooms?.toString() ?? '',
        bathrooms: listing.bathrooms?.toString() ?? '',
        area_value: listing.areaValue ?? '',
        area_unit: listing.areaUnit ?? '',
        year_built: listing.yearBuilt?.toString() ?? '',
        contact_name: listing.contactName ?? '',
        contact_email: listing.contactEmail ?? '',
        contact_phone: listing.contactPhone ?? '',
    })

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault()
        put(`/my-listings/${listing.id}`)
    }

    const approvedNotice =
        listing.status === 'approved' ? (
            <div className="flex items-start gap-3 rounded-lg border border-accent bg-accent-soft px-5 py-4 text-text">
                <Info className="mt-0.5 size-5 shrink-0 text-accent" />
                <p className="text-sm">{translations.approved_notice}</p>
            </div>
        ) : null

    return (
        <PublicLayout>
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
                        href="/profile"
                        className="inline-flex items-center gap-1.5 text-sm text-text-soft transition-colors hover:text-text"
                    >
                        <ChevronLeft className="size-4" />
                        {translations.back}
                    </Link>
                </div>
            </div>

            <div className="mx-auto max-w-3xl px-6 py-10">
                {flash.error && <FlashBanner message={flash.error} variant="error" coloredText className="mb-8" />}

                <ListingForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    submitLabel={translations.submit}
                    onSubmit={submit}
                    translations={translations}
                    propertyTypeLabels={propertyTypeLabels}
                    headerSlot={approvedNotice}
                />

                <div className="mt-12 border-t border-border pt-10">
                    <ImageUploader
                        listingId={listing.id}
                        images={listing.images}
                        translations={translations.images}
                        canManage
                    />
                </div>
            </div>
        </PublicLayout>
    )
}
