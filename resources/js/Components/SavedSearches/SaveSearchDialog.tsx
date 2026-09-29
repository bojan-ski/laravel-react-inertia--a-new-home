import { useRef } from 'react'
import { createPortal } from 'react-dom'
import { useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import { useFocusTrap } from '@/Components/UI/useFocusTrap'
import type { SaveSearchDialogProps } from '@/types/saved-search'

export default function SaveSearchDialog({ open, onClose, filters, suggestedName, translations }: SaveSearchDialogProps) {
    const nameRef = useRef<HTMLInputElement>(null)
    const panelRef = useFocusTrap(open, onClose, nameRef)
    const { data, setData, post, processing, errors, transform } = useForm({
        name: suggestedName,
        alerts_enabled: true,
    })

    if (!open) {
        return null
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault()
        event.stopPropagation()

        const activeFilters = Object.fromEntries(
            Object.entries(filters).filter(
                ([key, value]) => key !== 'sort' && (Array.isArray(value) ? value.length > 0 : value !== '')
            )
        )

        transform((current) => ({ ...activeFilters, ...current }))

        post('/saved-searches', {
            preserveScroll: true,
            onSuccess: () => onClose(),
        })
    }

    return createPortal(
        <div className="fixed inset-0 z-60 flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby="save-search-dialog-title"
                className="max-h-full w-full max-w-md overflow-y-auto rounded-xl border border-border bg-surface p-6 shadow-xl"
                onClick={(event) => event.stopPropagation()}
            >
                <h2 id="save-search-dialog-title" className="font-display text-2xl font-semibold text-text">
                    {translations.title}
                </h2>
                <p className="mt-2 text-sm leading-relaxed text-text-soft">{translations.description}</p>

                <form onSubmit={handleSubmit} className="mt-5 flex flex-col gap-5">
                    <div className="flex flex-col gap-1.5">
                        <label htmlFor="save-search-name" className="text-sm font-medium text-text">
                            {translations.name_label}
                        </label>
                        <input
                            ref={nameRef}
                            id="save-search-name"
                            type="text"
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            maxLength={100}
                            className="w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand"
                        />
                        {errors.name && <p className="text-sm text-status-rejected">{errors.name}</p>}
                    </div>

                    <label className="flex items-center gap-3 text-sm text-text">
                        <input
                            type="checkbox"
                            checked={data.alerts_enabled}
                            onChange={(event) => setData('alerts_enabled', event.target.checked)}
                            className="size-4 accent-brand"
                        />
                        {translations.alerts_label}
                    </label>

                    <div className="flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={processing}
                            className="rounded-lg border border-border-strong px-4 py-2 text-sm font-medium text-text-soft transition-colors hover:border-text hover:text-text disabled:opacity-50"
                        >
                            {translations.cancel}
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-50"
                        >
                            {translations.submit}
                        </button>
                    </div>
                </form>
            </div>
        </div>,
        document.body
    )
}
