import { Head, Link, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import FlashBanner from '@/Components/UI/FlashBanner'
import TextField from '@/Components/UI/TextField'
import AuthLayout from '@/Layouts/AuthLayout'
import type { ForgotPasswordPageProps } from '@/types/auth'
import type { SharedProps } from '@/types/shared'

export default function ForgotPassword({ translations }: ForgotPasswordPageProps) {
    const { flash } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    })

    function submit(e: FormEvent) {
        e.preventDefault()
        post('/forgot-password')
    }

    return (
        <AuthLayout>
            <Head title={translations.title} />

            {flash.success && <FlashBanner message={flash.success} variant="success" />}

            <h1 className="font-display text-3xl font-semibold text-text">{translations.title}</h1>
            <p className="mt-2 text-sm text-text-soft">{translations.subtitle}</p>

            <form onSubmit={submit} className="mt-8 flex flex-col gap-5">
                <TextField
                    id="email"
                    type="email"
                    label={translations.email}
                    autoComplete="email"
                    autoFocus
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                />

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-lg bg-brand px-4 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-60"
                >
                    {translations.submit}
                </button>
            </form>

            <p className="mt-6 text-center text-sm">
                <Link href="/login" className="text-text-soft transition-colors hover:text-text">
                    {translations.back_to_login}
                </Link>
            </p>
        </AuthLayout>
    )
}
