import { Head, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AgencyForm from '@/Components/Agency/AgencyForm'
import ListingApprovedNotice from '@/Components/Listings/ListingApprovedNotice'
import ListingFormHeader from '@/Components/Listings/ListingFormHeader'
import { formatDate } from '@/Components/Listings/listingFormat'
import FlashBanner from '@/Components/UI/FlashBanner'
import PublicLayout from '@/Layouts/PublicLayout'
import type { AgencyEditPageProps, AgencyFormData } from '@/types/agency'

export default function Edit({ agency, pendingChange, translations, countries }: AgencyEditPageProps) {
    const pendingValues = Object.fromEntries(
        Object.entries(pendingChange?.changes ?? {}).map(([field, value]) => [field, value ?? '']),
    ) as Partial<AgencyFormData>

    const { data, setData, put, processing, errors } = useForm<AgencyFormData>({
        name: agency.name,
        legal_name: agency.legalName ?? '',
        vat_id: agency.vatId ?? '',
        registration_number: agency.registrationNumber ?? '',
        registration_country: agency.registrationCountry ?? '',
        broker_register_number: agency.brokerRegisterNumber ?? '',
        email: agency.email,
        phone: agency.phone ?? '',
        website: agency.website ?? '',
        address: agency.address ?? '',
        description: agency.description ?? '',
        ...pendingValues,
    })

    const isApproved = agency.status === 'approved'

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault()
        put('/agency')
    }

    return (
        <PublicLayout back={{ href: '/agency', label: translations.back }} footer={false}>
            <Head title={translations.title} />

            <ListingFormHeader
                title={translations.title}
                subtitle={translations.subtitle}
                back={translations.back}
                backHref="/agency"
            />

            <div className="px-5 pt-6 pb-2 md:px-10 lg:-mt-3 lg:pt-0 lg:pb-10 xl:px-16">
                {pendingChange ? (
                    <FlashBanner
                        message={translations.pending_notice.replace(
                            ':date',
                            formatDate(pendingChange.submittedAt, 'long'),
                        )}
                        variant="info"
                        className=""
                    />
                ) : (
                    <ListingApprovedNotice message={isApproved ? translations.review_note : translations.status_note} />
                )}
            </div>

            <AgencyForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submitLabel={translations.submit}
                onSubmit={submit}
                translations={translations}
                countries={countries}
                barNote={isApproved ? translations.bar_note_approved : translations.bar_note_pending}
                cancelHref="/agency"
                cancelLabel={translations.cancel}
            />
        </PublicLayout>
    )
}
