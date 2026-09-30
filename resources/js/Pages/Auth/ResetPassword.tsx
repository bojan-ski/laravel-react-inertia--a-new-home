import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AuthHeader from '@/Components/Auth/AuthHeader'
import Button from '@/Components/UI/Button'
import FlashBanner from '@/Components/UI/FlashBanner'
import PasswordField from '@/Components/UI/PasswordField'
import TextField from '@/Components/UI/TextField'
import AuthLayout from '@/Layouts/AuthLayout'
import type { ResetPasswordPageProps } from '@/types/auth'
import type { SharedProps } from '@/types/shared'

export default function ResetPassword({ translations, layout, token, email }: ResetPasswordPageProps) {
    const { flash, ui } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    })

    function submit(e: FormEvent) {
        e.preventDefault()
        post('/reset-password', { onError: () => reset('password', 'password_confirmation') })
    }

    return (
        <AuthLayout translations={layout} image="password">
            <Head title={translations.title} />

            <div className="flex flex-col gap-8 lg:gap-10">
                {flash.error && <FlashBanner message={flash.error} variant="error" className="" />}

                <AuthHeader title={translations.title} subtitle={translations.subtitle} />

                <form onSubmit={submit} className="flex flex-col gap-8 lg:gap-10">
                    <div className="flex flex-col gap-6">
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

                        <PasswordField
                            id="password"
                            label={translations.password}
                            placeholder={translations.password_placeholder}
                            autoComplete="new-password"
                            autoFocus
                            showLabel={ui.show_password}
                            hideLabel={ui.hide_password}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                        />

                        <PasswordField
                            id="password_confirmation"
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

                    <Button
                        type="submit"
                        size="lg"
                        fullWidth
                        disabled={processing}
                        trailing={<span aria-hidden="true">→</span>}
                    >
                        {translations.submit}
                    </Button>
                </form>
            </div>
        </AuthLayout>
    )
}
