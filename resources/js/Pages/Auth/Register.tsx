import { Head, Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AccountTypeField from '@/Components/Auth/AccountTypeField'
import AcceptanceCheckbox from '@/Components/UI/AcceptanceCheckbox'
import TextField from '@/Components/UI/TextField'
import AuthLayout from '@/Layouts/AuthLayout'
import type { RegisterPageProps } from '@/types/auth'

export default function Register({ translations }: RegisterPageProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        account_type: 'regular_user',
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        terms_accepted: false,
        privacy_accepted: false,
    })

    const accountTypeOptions = [
        {
            value: 'regular_user',
            label: translations.regular_label,
            description: translations.regular_description,
        },
        {
            value: 'agency',
            label: translations.agency_label,
            description: translations.agency_description,
        },
    ]

    function submit(e: FormEvent) {
        e.preventDefault()
        post('/register', { onError: () => reset('password', 'password_confirmation') })
    }

    return (
        <AuthLayout>
            <Head title={translations.title} />

            <h1 className="font-display text-3xl font-semibold text-text">{translations.title}</h1>
            <p className="mt-2 text-sm text-text-soft">{translations.subtitle}</p>

            <form onSubmit={submit} className="mt-8 flex flex-col gap-5">
                <AccountTypeField
                    label={translations.account_type}
                    options={accountTypeOptions}
                    value={data.account_type}
                    error={errors.account_type}
                    onChange={(value) => setData('account_type', value)}
                />

                <TextField
                    id="name"
                    type="text"
                    label={translations.name}
                    autoComplete="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    error={errors.name}
                />

                <TextField
                    id="email"
                    type="email"
                    label={translations.email}
                    autoComplete="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                />

                <TextField
                    id="password"
                    type="password"
                    label={translations.password}
                    autoComplete="new-password"
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

                <div className="flex flex-col gap-3">
                    <AcceptanceCheckbox
                        id="terms_accepted"
                        checked={data.terms_accepted}
                        error={errors.terms_accepted}
                        onChange={(checked) => setData('terms_accepted', checked)}
                    >
                        {translations.terms_prefix}{' '}
                        <a
                            href="/terms"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="font-medium text-brand transition-colors hover:text-brand-dark"
                        >
                            {translations.terms_link}
                        </a>
                    </AcceptanceCheckbox>

                    <AcceptanceCheckbox
                        id="privacy_accepted"
                        checked={data.privacy_accepted}
                        error={errors.privacy_accepted}
                        onChange={(checked) => setData('privacy_accepted', checked)}
                    >
                        {translations.privacy_prefix}{' '}
                        <a
                            href="/privacy-policy"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="font-medium text-brand transition-colors hover:text-brand-dark"
                        >
                            {translations.privacy_link}
                        </a>
                    </AcceptanceCheckbox>
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
                {translations.have_account}{' '}
                <Link href="/login" className="font-medium text-brand transition-colors hover:text-brand-dark">
                    {translations.sign_in}
                </Link>
            </p>
        </AuthLayout>
    )
}
