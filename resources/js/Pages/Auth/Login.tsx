import { Head, Link, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AuthHeader from '@/Components/Auth/AuthHeader'
import AuthSwitchPrompt from '@/Components/Auth/AuthSwitchPrompt'
import Button from '@/Components/UI/Button'
import FlashBanner from '@/Components/UI/FlashBanner'
import PasswordField from '@/Components/UI/PasswordField'
import TextField from '@/Components/UI/TextField'
import AuthLayout from '@/Layouts/AuthLayout'
import type { LoginPageProps } from '@/types/auth'
import type { SharedProps } from '@/types/shared'

export default function Login({ translations, layout }: LoginPageProps) {
    const { flash, ui } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
    })

    function submit(e: FormEvent) {
        e.preventDefault()
        post('/login', { onError: () => reset('password') })
    }

    return (
        <AuthLayout translations={layout} image="sign-in">
            <Head title={translations.title} />

            <div className="flex flex-col gap-8 lg:gap-10">
                {flash.success && <FlashBanner message={flash.success} variant="success" className="" />}
                {flash.error && <FlashBanner message={flash.error} variant="error" className="" />}

                <AuthHeader title={translations.title} subtitle={translations.subtitle} />

                <form onSubmit={submit} className="flex flex-col gap-8 lg:gap-10">
                    <div className="flex flex-col gap-6">
                        <TextField
                            id="email"
                            type="email"
                            label={translations.email}
                            placeholder={translations.email_placeholder}
                            autoComplete="email"
                            autoFocus
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />

                        <PasswordField
                            id="password"
                            label={translations.password}
                            placeholder={translations.password_placeholder}
                            autoComplete="current-password"
                            showLabel={ui.show_password}
                            hideLabel={ui.hide_password}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                        />

                        <Link
                            href="/forgot-password"
                            className="inline-flex min-h-8 items-center self-start text-sm text-ink-muted transition-colors duration-150 hover:text-forest"
                        >
                            {translations.forgot}
                        </Link>
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
                            text={translations.no_account}
                            linkLabel={translations.create_one}
                            href="/register"
                        />
                    </div>
                </form>
            </div>
        </AuthLayout>
    )
}
