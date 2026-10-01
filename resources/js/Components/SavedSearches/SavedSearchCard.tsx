import { useState } from 'react'
import { router, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import { formatDate } from '@/Components/Listings/listingFormat'
import Button from '@/Components/UI/Button'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import TextField from '@/Components/UI/TextField'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { SavedSearchCardProps } from '@/types/saved-search'

export default function SavedSearchCard({ search, translations }: SavedSearchCardProps) {
    const [renaming, setRenaming] = useState(false)
    const [confirmingDelete, setConfirmingDelete] = useState(false)
    const [deleting, setDeleting] = useState(false)
    const [toggling, setToggling] = useState(false)
    const actionLabel = useActionLabel()
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
    const on = search.alertsEnabled

    return (
        <li className="flex flex-col gap-3.5 border-b border-rule py-6 lg:grid lg:grid-cols-[minmax(0,1fr)_240px_280px] lg:items-center lg:gap-10 lg:py-8">
            <div className="flex min-w-0 flex-col gap-3">
                {renaming ? (
                    <form onSubmit={submitRename} className="flex flex-col gap-3">
                        <TextField
                            id={`saved-search-name-${search.id}`}
                            label={translations.rename_label}
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            maxLength={100}
                            autoFocus
                            error={errors.name}
                        />
                        <div className="flex gap-3">
                            <Button type="submit" size="sm" disabled={processing}>
                                {translations.save_name}
                            </Button>
                            <Button variant="secondary" size="sm" onClick={cancelRename} disabled={processing}>
                                {translations.cancel}
                            </Button>
                        </div>
                    </form>
                ) : (
                    <h2 className="text-[22px] leading-tight font-light tracking-[-0.015em] wrap-break-word lg:text-[28px]">
                        {search.name}
                    </h2>
                )}

                <ul className="flex flex-wrap gap-1.5">
                    {chips.map((chip, index) => (
                        <li key={`${index}-${chip}`} className="border border-rule px-2.5 py-1.25 text-[13px]">
                            {chip}
                        </li>
                    ))}
                </ul>

                <p className="text-[13px] text-ink-muted">
                    {translations.created.replace(':date', formatDate(search.createdAt))}
                </p>
            </div>

            <button
                type="button"
                role="switch"
                aria-checked={on}
                aria-label={actionLabel(translations.alerts_label, search.name)}
                onClick={toggleAlerts}
                disabled={toggling}
                className="flex min-h-11 items-center gap-3 self-start text-sm text-ink disabled:cursor-not-allowed disabled:opacity-45 lg:self-center"
            >
                <span
                    aria-hidden="true"
                    className={`flex h-5.5 w-10 shrink-0 border border-ink p-0.75 transition-colors duration-150 ${
                        on ? 'justify-end bg-forest' : 'justify-start'
                    }`}
                >
                    <span className={`size-3.5 ${on ? 'bg-paper' : 'bg-ink'}`} />
                </span>
                <span>
                    {translations.alerts_label}
                    <span aria-hidden="true"> · {on ? translations.alerts_on : translations.alerts_off}</span>
                </span>
            </button>

            <div className="flex flex-wrap items-center gap-5 lg:justify-end">
                <Button
                    variant="strong-link"
                    href={search.url}
                    aria-label={actionLabel(translations.view_listings, search.name)}
                    className="text-sm"
                    trailing={<span aria-hidden="true">→</span>}
                >
                    {translations.view_listings}
                </Button>
                {!renaming && (
                    <Button
                        variant="link"
                        onClick={startRename}
                        aria-label={actionLabel(translations.rename, search.name)}
                    >
                        {translations.rename}
                    </Button>
                )}
                <Button
                    variant="link"
                    onClick={() => setConfirmingDelete(true)}
                    aria-label={actionLabel(translations.delete, search.name)}
                >
                    {translations.delete}
                </Button>
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
        </li>
    )
}
