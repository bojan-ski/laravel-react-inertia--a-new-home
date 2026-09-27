import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import FlashBanner from '@/Components/UI/FlashBanner'
import HoneypotField from '@/Components/UI/HoneypotField'
import TextField from '@/Components/UI/TextField'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ContactFormData, ContactPageProps } from '@/types/contact'
import type { SharedProps } from '@/types/shared'

export default function Show({ defaultEmail, translations }: ContactPageProps) {
    const { flash } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors, reset } = useForm<ContactFormData>({
        email: defaultEmail,
        subject: '',
        message: '',
        website: '',
    })

    function handleSubmit(event: FormEvent) {
        event.preventDefault()
        post('/contact', {
            preserveScroll: true,
            onSuccess: () => reset('subject', 'message'),
        })
    }

    return (
        <PublicLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto max-w-6xl px-6 py-14 md:py-20">
                    <p className="text-xs font-medium uppercase tracking-wider text-accent">{translations.eyebrow}</p>
                    <h1 className="mt-3 font-display text-4xl font-semibold text-text sm:text-5xl">{translations.title}</h1>
                    <p className="mt-4 max-w-2xl text-lg leading-relaxed text-text-soft">{translations.lead}</p>
                </div>
            </div>

            <div className="mx-auto grid max-w-6xl grid-cols-1 gap-12 px-6 py-14 md:py-16 lg:grid-cols-[1fr_1.5fr]">
                <aside>
                    <h2 className="font-display text-2xl font-semibold text-text">{translations.details_heading}</h2>
                    <div className="mt-4 flex flex-col gap-4">
                        {translations.details.map((paragraph) => (
                            <p key={paragraph} className="leading-relaxed text-text-soft">
                                {paragraph}
                            </p>
                        ))}
                    </div>
                </aside>

                <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm sm:p-8">
                    {flash.success && <FlashBanner message={flash.success} variant="success" />}

                    <form onSubmit={handleSubmit} className="flex flex-col gap-5">
                        <TextField
                            id="email"
                            type="email"
                            label={translations.fields.email}
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            error={errors.email}
                            autoComplete="email"
                            required
                        />

                        <TextField
                            id="subject"
                            label={translations.fields.subject}
                            value={data.subject}
                            onChange={(event) => setData('subject', event.target.value)}
                            error={errors.subject}
                            maxLength={150}
                            required
                        />

                        <div className="flex flex-col gap-1.5">
                            <label htmlFor="message" className="text-sm font-medium text-text-soft">
                                {translations.fields.message}
                            </label>
                            <textarea
                                id="message"
                                rows={7}
                                value={data.message}
                                onChange={(event) => setData('message', event.target.value)}
                                maxLength={5000}
                                required
                                className="w-full resize-y rounded-lg border border-border bg-surface px-4 py-3 text-text outline-none transition-colors focus:border-brand"
                            />
                            {errors.message && <p className="text-sm text-status-rejected">{errors.message}</p>}
                        </div>

                        <HoneypotField value={data.website} error={errors.website} onChange={(value) => setData('website', value)} />

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-full bg-brand px-6 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto sm:self-start"
                        >
                            {processing ? translations.sending : translations.submit}
                        </button>
                    </form>
                </div>
            </div>
        </PublicLayout>
    )
}
