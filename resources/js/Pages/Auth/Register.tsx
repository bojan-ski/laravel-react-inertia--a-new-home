import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AccountTypeField from '@/Components/Auth/AccountTypeField'
import AuthHeader from '@/Components/Auth/AuthHeader'
import AuthSwitchPrompt from '@/Components/Auth/AuthSwitchPrompt'
import AcceptanceCheckbox from '@/Components/UI/AcceptanceCheckbox'
import Button from '@/Components/UI/Button'
import HoneypotField from '@/Components/UI/HoneypotField'
import PasswordField from '@/Components/UI/PasswordField'
import TextField from '@/Components/UI/TextField'
import AuthLayout from '@/Layouts/AuthLayout'
import type { RegisterPageProps } from '@/types/auth'
import type { SharedProps } from '@/types/shared'

const policyLinkClasses = 'border-b border-current transition-colors duration-150 hover:text-forest'

export default function Register({ translations, layout }: RegisterPageProps) {
    const { ui } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors, reset } = useForm({
        account_type: 'regular_user',
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        terms_accepted: false,
        privacy_accepted: false,
        website: '',
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
        <AuthLayout translations={layout} image="register">
            <Head title={translations.title} />

            <div className="flex flex-col gap-8 lg:gap-10">
                <AuthHeader title={translations.title} subtitle={translations.subtitle} />

                <form onSubmit={submit} className="flex flex-col gap-8 lg:gap-10">
                    <div className="flex flex-col gap-5.5">
                        <AccountTypeField
                            label={translations.account_type}
                            options={accountTypeOptions}
                            value={data.account_type}
                            error={errors.account_type}
                            onChange={(value) => setData('account_type', value)}
                        />

                        <TextField
                            id="name"
                            aria-required
                            type="text"
                            label={translations.name}
                            placeholder={translations.name_placeholder}
                            autoComplete="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                        />

                        <TextField
                            id="email"
                            aria-required
                            type="email"
                            label={translations.email}
                            placeholder={translations.email_placeholder}
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />

                        <div className="grid gap-5.5 sm:grid-cols-2 sm:gap-x-6">
                            <PasswordField
                                id="password"
                                aria-required
                                label={translations.password}
                                placeholder={translations.password_placeholder}
                                autoComplete="new-password"
                                showLabel={ui.show_password}
                                hideLabel={ui.hide_password}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={errors.password}
                            />

                            <PasswordField
                                id="password_confirmation"
                                aria-required
                                label={translations.confirm_password}
                                placeholder={translations.password_placeholder}
                                autoComplete="new-password"
                                showLabel={ui.show_password}
                                hideLabel={ui.hide_password}
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                error={errors.password_confirmation}
                            />
                        </div>

                        <div className="mt-1 flex flex-col gap-2">
                            <AcceptanceCheckbox
                                id="terms_accepted"
                                checked={data.terms_accepted}
                                error={errors.terms_accepted}
                                onChange={(checked) => setData('terms_accepted', checked)}
                            >
                                {translations.terms_prefix}{' '}
                                <a href="/terms" target="_blank" rel="noopener noreferrer" className={policyLinkClasses}>
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
                                    className={policyLinkClasses}
                                >
                                    {translations.privacy_link}
                                </a>
                            </AcceptanceCheckbox>
                        </div>

                        <HoneypotField
                            value={data.website}
                            error={errors.website}
                            onChange={(value) => setData('website', value)}
                        />
                    </div>

                    <div className="flex flex-col gap-5">
                        <Button
                            type="submit"
                            size="lg"
                            fullWidth
                            disabled={processing}
                            trailing={<span aria-hidden="true">→</span>}
                        >
                            {translations.submit}
                        </Button>

                        <AuthSwitchPrompt
                            text={translations.have_account}
                            linkLabel={translations.sign_in}
                            href="/login"
                        />
                    </div>
                </form>
            </div>
        </AuthLayout>
    )
}
