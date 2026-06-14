import { Head, Link, useForm } from '@inertiajs/react'
import { ChevronLeft } from 'lucide-react'
import type { FormEvent } from 'react'
import AgencyForm from '@/Components/Agency/AgencyForm'
import PublicLayout from '@/Layouts/PublicLayout'
import type { AgencyEditPageProps, AgencyFormData } from '@/types/agency'

export default function Edit({ agency, translations }: AgencyEditPageProps) {
    const { data, setData, put, processing, errors } = useForm<AgencyFormData>({
        name: agency.name,
        email: agency.email,
        phone: agency.phone ?? '',
        website: agency.website ?? '',
        address: agency.address ?? '',
        description: agency.description ?? '',
    })

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault()
        put('/agency')
    }

    return (
        <PublicLayout>
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
                <AgencyForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    submitLabel={translations.submit}
                    onSubmit={submit}
                    translations={translations}
                    note={<p className="text-sm text-text-soft">{translations.status_note}</p>}
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
        </PublicLayout>
    )
}
