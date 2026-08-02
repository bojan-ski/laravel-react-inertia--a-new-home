import { Head, Link, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import FlashBanner from '@/Components/UI/FlashBanner'
import TextField from '@/Components/UI/TextField'
import AuthLayout from '@/Layouts/AuthLayout'
import type { LoginPageProps } from '@/types/auth'
import type { SharedProps } from '@/types/shared'

export default function Login({ translations }: LoginPageProps) {
    const { flash } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
    })

    function submit(e: FormEvent) {
        e.preventDefault()
        post('/login', { onError: () => reset('password') })
    }

    return (
        <AuthLayout>
            <Head title={translations.title} />

            {flash.error && <FlashBanner message={flash.error} variant="error" coloredText />}

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

                <div>
                    <TextField
                        id="password"
                        type="password"
                        label={translations.password}
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={errors.password}
                    />
                    <div className="mt-1.5 text-right">
                        <Link href="/forgot-password" className="text-sm text-text-soft transition-colors hover:text-text">
                            {translations.forgot}
                        </Link>
                    </div>
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-lg bg-brand px-4 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-60"
                >
                    {translations.submit}
                </button>
            </form>

            <p className="mt-6 text-center text-sm text-text-soft">
                {translations.no_account}{' '}
                <Link href="/register" className="font-medium text-brand transition-colors hover:text-brand-dark">
                    {translations.create_one}
                </Link>
            </p>
        </AuthLayout>
    )
}
