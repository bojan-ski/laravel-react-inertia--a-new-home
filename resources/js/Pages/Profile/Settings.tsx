import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { type FormEvent, useEffect, useState } from 'react'
import AccountPageHeader from '@/Components/Account/AccountPageHeader'
import FormSection from '@/Components/Forms/FormSection'
import Button from '@/Components/UI/Button'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import FlashBanner from '@/Components/UI/FlashBanner'
import PasswordField from '@/Components/UI/PasswordField'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ProfileSettingsPageProps } from '@/types/profile'
import type { SharedProps } from '@/types/shared'

export default function Settings({ translations, backHref, passwordUrl, deleteUrl }: ProfileSettingsPageProps) {
    const { flash, ui } = usePage<SharedProps>().props
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
        passwordForm.put(passwordUrl, {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        })
    }

    function requestDelete(e: FormEvent) {
        e.preventDefault()
        setConfirmDeleteOpen(true)
    }

    function confirmDelete() {
        deleteForm.delete(deleteUrl, {
            preserveScroll: true,
            onFinish: () => setConfirmDeleteOpen(false),
            onError: () => deleteForm.reset('password'),
        })
    }

    return (
        <PublicLayout>
            <Head title={translations.title} />

            <AccountPageHeader
                title={translations.title}
                subtitle={translations.subtitle}
                action={
                    <Link href={backHref} className="text-sm text-ink-muted transition-colors duration-150 hover:text-forest">
                        <span aria-hidden="true">←&nbsp;</span>
                        {translations.back}
                    </Link>
                }
            />

            <div className="px-5 pb-16 md:px-10 xl:px-16">
                <div className="border-b border-rule [&>section:first-child]:border-t-0">
                    <FormSection
                        id="password"
                        number="01"
                        title={translations.password.heading}
                        note={translations.password.note}
                    >
                        <form onSubmit={submitPassword} className="col-span-2 flex max-w-140 flex-col gap-6.5">
                            {banner && (
                                <FlashBanner
                                    message={banner}
                                    variant="success"
                                    className=""
                                    onDismiss={() => setBanner(null)}
                                    dismissLabel={ui.dismiss}
                                />
                            )}

                            <PasswordField
                                id="current_password"
                                aria-required
                                label={translations.password.current}
                                autoComplete="current-password"
                                value={passwordForm.data.current_password}
                                onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                error={passwordForm.errors.current_password}
                                showLabel={ui.show_password}
                                hideLabel={ui.hide_password}
                            />

                            <PasswordField
                                id="new_password"
                                aria-required
                                label={translations.password.new}
                                autoComplete="new-password"
                                value={passwordForm.data.password}
                                onChange={(e) => passwordForm.setData('password', e.target.value)}
                                error={passwordForm.errors.password}
                                showLabel={ui.show_password}
                                hideLabel={ui.hide_password}
                            />

                            <PasswordField
                                id="password_confirmation"
                                aria-required
                                label={translations.password.confirm}
                                autoComplete="new-password"
                                value={passwordForm.data.password_confirmation}
                                onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                error={passwordForm.errors.password_confirmation}
                                showLabel={ui.show_password}
                                hideLabel={ui.hide_password}
                            />

                            <Button
                                type="submit"
                                disabled={passwordForm.processing}
                                trailing={<span aria-hidden="true">→</span>}
                                className="min-h-13 gap-10! self-start"
                            >
                                {translations.password.submit}
                            </Button>
                        </form>
                    </FormSection>

                    <FormSection
                        id="delete-account"
                        number="02"
                        title={translations.delete.heading}
                        note={translations.delete.note}
                    >
                        <form onSubmit={requestDelete} className="col-span-2 flex max-w-140 flex-col gap-6.5">
                            <PasswordField
                                id="delete_password"
                                label={translations.delete.password}
                                autoComplete="current-password"
                                value={deleteForm.data.password}
                                onChange={(e) => deleteForm.setData('password', e.target.value)}
                                error={deleteForm.errors.password}
                                required
                                showLabel={ui.show_password}
                                hideLabel={ui.hide_password}
                            />

                            <Button
                                type="submit"
                                variant="ink-outline"
                                disabled={deleteForm.processing}
                                className="min-h-13 self-start"
                            >
                                {translations.delete.submit}
                            </Button>
                        </form>
                    </FormSection>
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
        </PublicLayout>
    )
}
