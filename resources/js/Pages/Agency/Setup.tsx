import { Head, Link, useForm } from '@inertiajs/react'
import { Info } from 'lucide-react'
import type { FormEvent } from 'react'
import TextField from '@/Components/UI/TextField'
import PublicLayout from '@/Layouts/PublicLayout'
import type { AgencySetupFormData, AgencySetupPageProps } from '@/types/agency'

export default function Setup({ translations }: AgencySetupPageProps) {
    const { data, setData, post, processing, errors } = useForm<AgencySetupFormData>({
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
        <PublicLayout>
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

                <form onSubmit={submit} className="mt-10 space-y-12">
                    <section>
                        <h2 className="font-display text-xl font-semibold text-text">
                            {translations.sections.identity}
                        </h2>

                        <div className="mt-6 flex flex-col gap-5">
                            <TextField
                                id="name"
                                type="text"
                                label={translations.fields.name}
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                error={errors.name}
                            />

                            <div className="flex flex-col gap-1.5">
                                <label htmlFor="description" className="text-sm font-medium text-text-soft">
                                    {translations.fields.description}
                                </label>
                                <textarea
                                    id="description"
                                    rows={5}
                                    className="w-full rounded-lg border border-border bg-surface px-4 py-3 text-text outline-none transition-colors focus:border-brand"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                {errors.description && (
                                    <p className="text-sm text-status-rejected">{errors.description}</p>
                                )}
                            </div>
                        </div>
                    </section>

                    <div className="border-t border-border" />

                    <section>
                        <h2 className="font-display text-xl font-semibold text-text">
                            {translations.sections.contact}
                        </h2>

                        <div className="mt-6 flex flex-col gap-5">
                            <div className="flex flex-col gap-1.5">
                                <TextField
                                    id="email"
                                    type="email"
                                    label={translations.fields.email}
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    error={errors.email}
                                />
                                <p className="text-sm text-text-muted">{translations.fields.email_note}</p>
                            </div>

                            <TextField
                                id="phone"
                                type="text"
                                label={translations.fields.phone}
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                error={errors.phone}
                            />

                            <TextField
                                id="website"
                                type="text"
                                label={translations.fields.website}
                                placeholder="https://"
                                value={data.website}
                                onChange={(e) => setData('website', e.target.value)}
                                error={errors.website}
                            />

                            <TextField
                                id="address"
                                type="text"
                                label={translations.fields.address}
                                value={data.address}
                                onChange={(e) => setData('address', e.target.value)}
                                error={errors.address}
                            />
                        </div>
                    </section>

                    <div className="flex flex-col gap-4">
                        <div className="flex flex-col-reverse items-stretch gap-4 sm:flex-row sm:items-center sm:justify-end">
                            <Link
                                href="/"
                                className="text-center text-sm text-text-soft transition-colors hover:text-text"
                            >
                                {translations.back}
                            </Link>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-full bg-brand px-6 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-60 sm:w-auto"
                            >
                                {translations.submit}
                            </button>
                        </div>

                        <p className="text-sm text-text-muted">{translations.submit_note}</p>
                    </div>
                </form>
            </div>
        </PublicLayout>
    )
}
