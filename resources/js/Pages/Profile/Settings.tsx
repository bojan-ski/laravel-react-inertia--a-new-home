import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { ChevronLeft, X } from 'lucide-react'
import { type FormEvent, useEffect, useState } from 'react'
import TextField from '@/Components/UI/TextField'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ProfileSettingsPageProps } from '@/types/profile'
import type { SharedProps } from '@/types/shared'

export default function Settings({ user, translations }: ProfileSettingsPageProps) {
    const { flash } = usePage<SharedProps>().props
    const [banner, setBanner] = useState<string | null>(null)

    useEffect(() => {
        setBanner(flash.success)
    }, [flash.success])

    const accountForm = useForm({
        name: user.name,
        email: user.email,
    })

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    })

    function submitAccount(e: FormEvent) {
        e.preventDefault()
        accountForm.put('/profile/settings/account', { preserveScroll: true })
    }

    function submitPassword(e: FormEvent) {
        e.preventDefault()
        passwordForm.put('/profile/settings/password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        })
    }

    return (
        <PublicLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-10 sm:flex-row sm:items-start sm:justify-between md:py-14">
                    <div>
                        <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                            {translations.title}
                        </h1>
                        <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                    </div>

                    <Link
                        href="/profile"
                        className="inline-flex items-center gap-1.5 text-sm text-text-soft transition-colors hover:text-text"
                    >
                        <ChevronLeft className="size-4" />
                        {translations.back}
                    </Link>
                </div>
            </div>

            <div className="mx-auto max-w-2xl px-6 py-10">
                {banner && (
                    <div className="mb-8 flex items-start justify-between gap-4 rounded-lg border border-status-approved/30 bg-status-approved/10 px-4 py-3 text-sm text-text">
                        <span>{banner}</span>
                        <button
                            type="button"
                            onClick={() => setBanner(null)}
                            className="text-text-soft transition-colors hover:text-text"
                            aria-label="Dismiss"
                        >
                            <X className="size-4" />
                        </button>
                    </div>
                )}

                <div className="space-y-12">
                    <section>
                        <h2 className="font-display text-xl font-semibold text-text">{translations.account.heading}</h2>
                        <p className="mt-1 text-sm text-text-soft">{translations.account.note}</p>

                        <form onSubmit={submitAccount} className="mt-6 flex flex-col gap-5">
                            <TextField
                                id="name"
                                type="text"
                                label={translations.account.name}
                                autoComplete="name"
                                value={accountForm.data.name}
                                onChange={(e) => accountForm.setData('name', e.target.value)}
                                error={accountForm.errors.name}
                            />

                            <TextField
                                id="email"
                                type="email"
                                label={translations.account.email}
                                autoComplete="email"
                                value={accountForm.data.email}
                                onChange={(e) => accountForm.setData('email', e.target.value)}
                                error={accountForm.errors.email}
                            />

                            <div>
                                <button
                                    type="submit"
                                    disabled={accountForm.processing}
                                    className="w-full rounded-lg bg-brand px-5 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-60 sm:w-auto"
                                >
                                    {translations.account.submit}
                                </button>
                            </div>
                        </form>
                    </section>

                    <div className="border-t border-border" />

                    <section>
                        <h2 className="font-display text-xl font-semibold text-text">{translations.password.heading}</h2>
                        <p className="mt-1 text-sm text-text-soft">{translations.password.note}</p>

                        <form onSubmit={submitPassword} className="mt-6 flex flex-col gap-5">
                            <TextField
                                id="current_password"
                                type="password"
                                label={translations.password.current}
                                autoComplete="current-password"
                                value={passwordForm.data.current_password}
                                onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                error={passwordForm.errors.current_password}
                            />

                            <TextField
                                id="password"
                                type="password"
                                label={translations.password.new}
                                autoComplete="new-password"
                                value={passwordForm.data.password}
                                onChange={(e) => passwordForm.setData('password', e.target.value)}
                                error={passwordForm.errors.password}
                            />

                            <TextField
                                id="password_confirmation"
                                type="password"
                                label={translations.password.confirm}
                                autoComplete="new-password"
                                value={passwordForm.data.password_confirmation}
                                onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                error={passwordForm.errors.password_confirmation}
                            />

                            <div>
                                <button
                                    type="submit"
                                    disabled={passwordForm.processing}
                                    className="w-full rounded-lg bg-brand px-5 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-60 sm:w-auto"
                                >
                                    {translations.password.submit}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </PublicLayout>
    )
}
