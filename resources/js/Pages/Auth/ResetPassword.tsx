import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import FlashBanner from '@/Components/UI/FlashBanner'
import TextField from '@/Components/UI/TextField'
import AuthLayout from '@/Layouts/AuthLayout'
import type { ResetPasswordPageProps } from '@/types/auth'
import type { SharedProps } from '@/types/shared'

export default function ResetPassword({ translations, token, email }: ResetPasswordPageProps) {
    const { flash } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    })

    function submit(e: FormEvent) {
        e.preventDefault()
        post('/reset-password')
    }

    return (
        <AuthLayout>
            <Head title={translations.title} />

            {flash.error && <FlashBanner message={flash.error} variant="error" coloredText />}

            <h1 className="font-display text-3xl font-semibold text-text">{translations.title}</h1>
            <p className="mt-2 text-sm text-text-soft">{translations.subtitle}</p>

            <form onSubmit={submit} className="mt-8 flex flex-col gap-5">
                <input type="hidden" value={data.token} readOnly />

                <TextField
                    id="email"
                    type="email"
                    label={translations.email}
                    autoComplete="email"
                    readOnly
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                />

                <TextField
                    id="password"
                    type="password"
                    label={translations.password}
                    autoComplete="new-password"
                    autoFocus
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                />

                <TextField
                    id="password_confirmation"
                    type="password"
                    label={translations.confirm_password}
                    autoComplete="new-password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    error={errors.password_confirmation}
                />

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-lg bg-brand px-4 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-60"
                >
                    {translations.submit}
                </button>
            </form>
        </AuthLayout>
    )
}
