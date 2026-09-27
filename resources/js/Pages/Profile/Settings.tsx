import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { ChevronLeft } from 'lucide-react'
import { type FormEvent, useEffect, useState } from 'react'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import FlashBanner from '@/Components/UI/FlashBanner'
import TextField from '@/Components/UI/TextField'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { ProfileSettingsPageProps } from '@/types/profile'
import type { SharedProps } from '@/types/shared'

export default function Settings({ translations }: ProfileSettingsPageProps) {
    const { flash } = usePage<SharedProps>().props
    const [banner, setBanner] = useState<string | null>(null)
    const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false)

    useEffect(() => {
        setBanner(flash.success)
    }, [flash.success])

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    })

    const deleteForm = useForm({
        password: '',
    })

    function submitPassword(e: FormEvent) {
        e.preventDefault()
        passwordForm.put('/profile/settings/password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        })
    }

    function requestDelete(e: FormEvent) {
        e.preventDefault()
        setConfirmDeleteOpen(true)
    }

    function confirmDelete() {
        deleteForm.delete('/profile', {
            preserveScroll: true,
            onFinish: () => setConfirmDeleteOpen(false),
            onError: () => deleteForm.reset('password'),
        })
    }

    return (
        <ListingsLayout>
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
                    <FlashBanner message={banner} variant="success" className="mb-8" onDismiss={() => setBanner(null)} />
                )}

                <div className="space-y-12">
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

                    <div className="border-t border-border" />

                    <section className="rounded-2xl border border-status-rejected/30 bg-status-rejected/5 p-6">
                        <h2 className="font-display text-xl font-semibold text-status-rejected">
                            {translations.delete.heading}
                        </h2>
                        <p className="mt-1 text-sm leading-relaxed text-text-soft">{translations.delete.note}</p>

                        <form onSubmit={requestDelete} className="mt-6 flex flex-col gap-5">
                            <TextField
                                id="delete_password"
                                type="password"
                                label={translations.delete.password}
                                autoComplete="current-password"
                                value={deleteForm.data.password}
                                onChange={(e) => deleteForm.setData('password', e.target.value)}
                                error={deleteForm.errors.password}
                                required
                            />

                            <div>
                                <button
                                    type="submit"
                                    disabled={deleteForm.processing}
                                    className="w-full rounded-lg bg-status-rejected px-5 py-3 text-sm font-semibold text-text-inverse transition-opacity hover:opacity-90 disabled:opacity-60 sm:w-auto"
                                >
                                    {translations.delete.submit}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>

            <ConfirmDialog
                open={confirmDeleteOpen}
                title={translations.delete.confirm.title}
                description={translations.delete.confirm.description}
                confirmLabel={translations.delete.confirm.confirm}
                cancelLabel={translations.delete.confirm.cancel}
                processing={deleteForm.processing}
                onConfirm={confirmDelete}
                onCancel={() => setConfirmDeleteOpen(false)}
            />
        </ListingsLayout>
    )
}
