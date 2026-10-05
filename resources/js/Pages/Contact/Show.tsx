import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import Button from '@/Components/UI/Button'
import FlashBanner from '@/Components/UI/FlashBanner'
import HoneypotField from '@/Components/UI/HoneypotField'
import Kicker from '@/Components/UI/Kicker'
import TextAreaField from '@/Components/UI/TextAreaField'
import TextField from '@/Components/UI/TextField'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ContactFormData, ContactPageProps } from '@/types/contact'
import type { SharedProps } from '@/types/shared'

export default function Show({ defaultEmail, translations }: ContactPageProps) {
    const { flash } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors } = useForm<ContactFormData>({
        email: defaultEmail,
        subject: '',
        message: '',
        website: '',
    })

    function handleSubmit(event: FormEvent) {
        event.preventDefault()
        post('/contact', {
            preserveScroll: true,
            onSuccess: (page) => {
                if ((page.props.flash as SharedProps['flash']).success) {
                    setData((current) => ({ ...current, subject: '', message: '' }))
                }
            },
        })
    }

    return (
        <PublicLayout>
            <Head title={translations.title} />

            <div className="mx-auto flex max-w-360 flex-col lg:grid lg:grid-cols-[minmax(0,6fr)_minmax(0,1fr)_minmax(0,5fr)] lg:grid-rows-[auto_1fr] lg:gap-y-14 lg:px-16 lg:pt-18 lg:pb-30">
                <div className="flex flex-col gap-3.5 px-5 pt-9 pb-7 md:px-10 lg:col-start-1 lg:row-start-1 lg:gap-5 lg:p-0">
                    <Kicker size="sm" className="lg:text-xs">
                        {translations.eyebrow}
                    </Kicker>
                    <h1 className="text-[52px] leading-[0.92] font-extralight tracking-[-0.045em] wrap-break-word md:text-[80px] lg:text-[112px] lg:leading-[0.9]">
                        {translations.title}
                    </h1>
                    <p className="text-base/normal font-light lg:max-w-[30em] lg:text-xl/normal">{translations.lead}</p>
                </div>

                <div className="bg-sand px-5 py-7 md:px-10 lg:col-start-3 lg:row-span-2 lg:row-start-1 lg:self-start lg:p-12">
                    <form onSubmit={handleSubmit} className="flex flex-col gap-6 lg:gap-7">
                        {flash.success && <FlashBanner message={flash.success} variant="success" className="" />}

                        <TextField
                            id="email"
                            type="email"
                            label={translations.fields.email}
                            placeholder={translations.placeholders.email}
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            error={errors.email}
                            autoComplete="email"
                            required
                        />

                        <TextField
                            id="subject"
                            label={translations.fields.subject}
                            placeholder={translations.placeholders.subject}
                            value={data.subject}
                            onChange={(event) => setData('subject', event.target.value)}
                            error={errors.subject}
                            maxLength={150}
                            required
                        />

                        <TextAreaField
                            id="message"
                            rows={6}
                            label={translations.fields.message}
                            placeholder={translations.placeholders.message}
                            value={data.message}
                            onChange={(event) => setData('message', event.target.value)}
                            error={errors.message}
                            maxLength={5000}
                            required
                        />

                        <HoneypotField value={data.website} error={errors.website} onChange={(value) => setData('website', value)} />

                        <Button
                            type="submit"
                            size="lg"
                            fullWidth
                            disabled={processing}
                            trailing={<span aria-hidden="true">→</span>}
                        >
                            {processing ? translations.sending : translations.submit}
                        </Button>
                    </form>
                </div>

                <div className="flex flex-col gap-2.5 px-5 pt-7 pb-10 md:px-10 lg:col-start-1 lg:row-start-2 lg:gap-3.5 lg:self-start lg:border-t lg:border-rule lg:p-0 lg:pt-7">
                    <Kicker as="h2" size="sm" className="lg:text-xs">
                        {translations.details_heading}
                    </Kicker>
                    {translations.details.map((paragraph) => (
                        <p key={paragraph} className="text-sm/[1.6] font-book lg:max-w-[34em] lg:text-base/[1.7]">
                            {paragraph}
                        </p>
                    ))}
                </div>
            </div>
        </PublicLayout>
    )
}
