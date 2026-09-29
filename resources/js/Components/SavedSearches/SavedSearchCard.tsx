import { useState } from 'react'
import { Link, router, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import { Bell, BellOff, Pencil, Trash2 } from 'lucide-react'
import { formatDate } from '@/Components/Listings/listingFormat'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import type { SavedSearchCardProps } from '@/types/saved-search'

export default function SavedSearchCard({ search, translations }: SavedSearchCardProps) {
    const [renaming, setRenaming] = useState(false)
    const [confirmingDelete, setConfirmingDelete] = useState(false)
    const [deleting, setDeleting] = useState(false)
    const [toggling, setToggling] = useState(false)
    const { data, setData, patch, processing, errors, clearErrors } = useForm({ name: search.name })

    function startRename() {
        setData('name', search.name)
        clearErrors()
        setRenaming(true)
    }

    function submitRename(event: FormEvent) {
        event.preventDefault()

        patch(`/saved-searches/${search.id}`, {
            preserveScroll: true,
            onSuccess: () => setRenaming(false),
        })
    }

    function cancelRename() {
        clearErrors()
        setRenaming(false)
    }

    function toggleAlerts() {
        router.patch(
            `/saved-searches/${search.id}`,
            { alerts_enabled: !search.alertsEnabled },
            {
                preserveScroll: true,
                onStart: () => setToggling(true),
                onFinish: () => setToggling(false),
            }
        )
    }

    function confirmDelete() {
        router.delete(`/saved-searches/${search.id}`, {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false)
                setConfirmingDelete(false)
            },
        })
    }

    const chips = search.summary.length > 0 ? search.summary : [translations.no_filters]

    return (
        <article className="flex flex-col gap-4 rounded-xl border border-border bg-surface p-5 shadow-sm sm:p-6">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                {renaming ? (
                    <form onSubmit={submitRename} className="flex flex-1 flex-col gap-2">
                        <label htmlFor={`saved-search-name-${search.id}`} className="sr-only">
                            {translations.rename_label}
                        </label>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <input
                                id={`saved-search-name-${search.id}`}
                                type="text"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                maxLength={100}
                                autoFocus
                                className="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text outline-none transition-colors focus:border-brand"
                            />
                            <div className="flex gap-2">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-50"
                                >
                                    {translations.save_name}
                                </button>
                                <button
                                    type="button"
                                    onClick={cancelRename}
                                    disabled={processing}
                                    className="rounded-lg border border-border-strong px-4 py-2 text-sm font-medium text-text-soft transition-colors hover:border-text hover:text-text disabled:opacity-50"
                                >
                                    {translations.cancel}
                                </button>
                            </div>
                        </div>
                        {errors.name && <p className="text-sm text-status-rejected">{errors.name}</p>}
                    </form>
                ) : (
                    <div className="flex min-w-0 items-start gap-2">
                        <h2 className="wrap-break-word text-lg font-semibold text-text">{search.name}</h2>
                        <button
                            type="button"
                            onClick={startRename}
                            aria-label={translations.rename}
                            className="mt-1 shrink-0 text-text-muted transition-colors hover:text-text"
                        >
                            <Pencil className="size-4" />
                        </button>
                    </div>
                )}

                <button
                    type="button"
                    onClick={toggleAlerts}
                    disabled={toggling}
                    aria-pressed={search.alertsEnabled}
                    className={`flex shrink-0 items-center gap-2 self-start rounded-full border px-3 py-1.5 text-sm font-medium transition-colors disabled:opacity-50 ${
                        search.alertsEnabled
                            ? 'border-brand bg-brand-soft text-brand'
                            : 'border-border text-text-soft hover:text-text'
                    }`}
                >
                    {search.alertsEnabled ? <Bell className="size-4" /> : <BellOff className="size-4" />}
                    {translations.alerts_label}: {search.alertsEnabled ? translations.alerts_on : translations.alerts_off}
                </button>
            </div>

            <ul className="flex flex-wrap gap-2">
                {chips.map((chip, index) => (
                    <li key={`${index}-${chip}`} className="rounded-full bg-surface-muted px-3 py-1 text-sm text-text-soft">
                        {chip}
                    </li>
                ))}
            </ul>

            <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
                <p className="text-sm text-text-muted">
                    {translations.created.replace(':date', formatDate(search.createdAt, 'long'))}
                </p>

                <div className="flex items-center gap-4">
                    <Link
                        href={search.url}
                        className="text-sm font-semibold text-brand transition-colors hover:text-brand-dark"
                    >
                        {translations.view_listings}
                    </Link>
                    <button
                        type="button"
                        onClick={() => setConfirmingDelete(true)}
                        className="flex items-center gap-1.5 text-sm font-medium text-text-soft transition-colors hover:text-status-rejected"
                    >
                        <Trash2 className="size-4" />
                        {translations.delete}
                    </button>
                </div>
            </div>

            <ConfirmDialog
                open={confirmingDelete}
                title={translations.confirm_delete.title}
                description={translations.confirm_delete.description.replace(':name', search.name)}
                confirmLabel={translations.confirm_delete.confirm}
                cancelLabel={translations.confirm_delete.cancel}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setConfirmingDelete(false)}
            />
        </article>
    )
}
