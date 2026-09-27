import { Head, Link, useForm } from '@inertiajs/react'
import { Info } from 'lucide-react'
import type { FormEvent } from 'react'
import AgencyForm from '@/Components/Agency/AgencyForm'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { AgencyFormData, AgencySetupPageProps } from '@/types/agency'

export default function Setup({ translations }: AgencySetupPageProps) {
    const { data, setData, post, processing, errors } = useForm<AgencyFormData>({
        name: '',
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
        <ListingsLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto max-w-7xl px-6 py-10 md:py-14">
                    <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                        {translations.title}
                    </h1>
                    <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                </div>
            </div>

            <div className="mx-auto max-w-2xl px-6 py-10">
                <div className="flex items-start gap-3 rounded-lg border border-accent bg-accent-soft px-4 py-3">
                    <Info className="mt-0.5 size-5 shrink-0 text-accent" />
                    <p className="text-sm text-text">{translations.helper}</p>
                </div>

                <div className="mt-10">
                    <AgencyForm
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        submitLabel={translations.submit}
                        onSubmit={submit}
                        translations={translations}
                        note={<p className="text-sm text-text-muted">{translations.submit_note}</p>}
                        cancelSlot={
                            <Link
                                href="/"
                                className="text-center text-sm text-text-soft transition-colors hover:text-text"
                            >
                                {translations.back}
                            </Link>
                        }
                    />
                </div>
            </div>
        </ListingsLayout>
    )
}
