import { Head, Link, useForm } from '@inertiajs/react'
import { ChevronLeft, Clock } from 'lucide-react'
import type { FormEvent } from 'react'
import AgencyForm from '@/Components/Agency/AgencyForm'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { AgencyEditPageProps, AgencyFormData } from '@/types/agency'

export default function Edit({ agency, pendingChange, translations, usStates }: AgencyEditPageProps) {
    const pendingValues = Object.fromEntries(
        Object.entries(pendingChange?.changes ?? {}).map(([field, value]) => [field, value ?? '']),
    ) as Partial<AgencyFormData>

    const { data, setData, put, processing, errors } = useForm<AgencyFormData>({
        name: agency.name,
        legal_name: agency.legalName ?? '',
        ein: agency.ein ?? '',
        license_number: agency.licenseNumber ?? '',
        license_state: agency.licenseState ?? '',
        email: agency.email,
        phone: agency.phone ?? '',
        website: agency.website ?? '',
        address: agency.address ?? '',
        description: agency.description ?? '',
        ...pendingValues,
    })

    const isApproved = agency.status === 'approved'
    const submittedOn = pendingChange ? new Date(pendingChange.submittedAt).toLocaleDateString() : null

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault()
        put('/agency')
    }

    return (
        <ListingsLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto flex max-w-2xl flex-col gap-4 px-6 py-10 sm:flex-row sm:items-start sm:justify-between md:py-14">
                    <div>
                        <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                            {translations.title}
                        </h1>
                        <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                    </div>

                    <Link
                        href="/agency"
                        className="inline-flex items-center gap-1 text-sm text-text-soft transition-colors hover:text-text"
                    >
                        <ChevronLeft className="size-4" />
                        {translations.back}
                    </Link>
                </div>
            </div>

            <div className="mx-auto max-w-2xl px-6 py-10">
                {submittedOn && (
                    <div className="mb-8 flex items-start gap-3 rounded-lg border border-accent bg-accent-soft px-5 py-4 text-text">
                        <Clock className="mt-0.5 size-5 shrink-0 text-accent" />
                        <p className="text-sm">{translations.pending_notice.replace(':date', submittedOn)}</p>
                    </div>
                )}

                <AgencyForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    submitLabel={translations.submit}
                    onSubmit={submit}
                    translations={translations}
                    usStates={usStates}
                    note={
                        <p className="text-sm text-text-soft">
                            {isApproved ? translations.review_note : translations.status_note}
                        </p>
                    }
                    cancelSlot={
                        <Link
                            href="/agency"
                            className="text-center text-sm text-text-soft transition-colors hover:text-text"
                        >
                            {translations.cancel}
                        </Link>
                    }
                />
            </div>
        </ListingsLayout>
    )
}
