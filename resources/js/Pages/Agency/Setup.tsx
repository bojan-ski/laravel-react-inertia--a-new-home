import { Head, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AgencyForm from '@/Components/Agency/AgencyForm'
import ListingApprovedNotice from '@/Components/Listings/ListingApprovedNotice'
import ListingFormHeader from '@/Components/Listings/ListingFormHeader'
import PublicLayout from '@/Layouts/PublicLayout'
import type { AgencyFormData, AgencySetupPageProps } from '@/types/agency'

export default function Setup({ translations, countries }: AgencySetupPageProps) {
    const { data, setData, post, processing, errors } = useForm<AgencyFormData>({
        name: '',
        legal_name: '',
        vat_id: '',
        registration_number: '',
        registration_country: '',
        broker_register_number: '',
        email: '',
        phone: '',
        website: '',
        address: '',
        description: '',
    })

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault()
        post('/agency/setup')
    }

    return (
        <PublicLayout back={{ href: '/', label: translations.back }} footer={false}>
            <Head title={translations.title} />

            <ListingFormHeader
                title={translations.title}
                subtitle={translations.subtitle}
                back={translations.back}
                backHref="/"
            />

            <div className="px-5 pt-6 pb-2 md:px-10 lg:-mt-3 lg:pt-0 lg:pb-10 xl:px-16">
                <ListingApprovedNotice message={translations.helper} />
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
                barNote={translations.submit_note}
                cancelHref="/"
                cancelLabel={translations.cancel}
            />
        </PublicLayout>
    )
}
