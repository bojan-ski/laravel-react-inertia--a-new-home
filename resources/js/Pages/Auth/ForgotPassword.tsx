import { Head, Link, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AuthHeader from '@/Components/Auth/AuthHeader'
import Button from '@/Components/UI/Button'
import FlashBanner from '@/Components/UI/FlashBanner'
import TextField from '@/Components/UI/TextField'
import AuthLayout from '@/Layouts/AuthLayout'
import type { ForgotPasswordPageProps } from '@/types/auth'
import type { SharedProps } from '@/types/shared'

export default function ForgotPassword({ translations, layout }: ForgotPasswordPageProps) {
    const { flash } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    })

    function submit(e: FormEvent) {
        e.preventDefault()
        post('/forgot-password')
    }

    return (
        <AuthLayout translations={layout} image="password">
            <Head title={translations.title} />

            <div className="flex flex-col gap-8 lg:gap-10">
                {flash.success && <FlashBanner message={flash.success} variant="success" className="" />}

                <AuthHeader title={translations.title} subtitle={translations.subtitle} />

                <form onSubmit={submit} className="flex flex-col gap-8 lg:gap-10">
                    <TextField
                        id="email"
                        aria-required
                        type="email"
                        label={translations.email}
                        placeholder={translations.email_placeholder}
                        autoComplete="email"
                        autoFocus
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        error={errors.email}
                    />

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

                        <Link
                            href="/login"
                            className="self-start text-sm text-ink-muted transition-colors duration-150 hover:text-forest"
                        >
                            {translations.back_to_login}
                        </Link>
                    </div>
                </form>
            </div>
        </AuthLayout>
    )
}
