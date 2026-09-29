import { useRef } from 'react'
import { useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import Button from '@/Components/UI/Button'
import Checkbox from '@/Components/UI/Checkbox'
import Dialog, { DialogActions } from '@/Components/UI/Dialog'
import TextField from '@/Components/UI/TextField'
import type { SaveSearchDialogProps } from '@/types/saved-search'

export default function SaveSearchDialog({
    open,
    onClose,
    filters,
    suggestedName,
    summary,
    translations,
}: SaveSearchDialogProps) {
    const nameRef = useRef<HTMLInputElement>(null)
    const { data, setData, post, processing, errors, transform } = useForm({
        name: suggestedName,
        alerts_enabled: true,
    })

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

    return (
        <Dialog
            open={open}
            onClose={onClose}
            title={translations.title}
            description={translations.description}
            initialFocusRef={nameRef}
        >
            {summary.length > 0 && (
                <ul className="flex flex-wrap gap-1.5">
                    {summary.map((part) => (
                        <li key={part} className="border border-rule px-2.5 py-1.25 text-[13px]">
                            {part}
                        </li>
                    ))}
                </ul>
            )}

            <form onSubmit={handleSubmit} className="flex flex-col gap-5.5">
                <TextField
                    ref={nameRef}
                    id="save-search-name"
                    label={translations.name_label}
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    maxLength={100}
                    error={errors.name}
                />

                <Checkbox
                    checked={data.alerts_enabled}
                    onChange={(event) => setData('alerts_enabled', event.target.checked)}
                    className="min-h-10 py-2.5 text-[15px]"
                >
                    {translations.alerts_label}
                </Checkbox>

                <DialogActions>
                    <Button variant="secondary" onClick={onClose} disabled={processing}>
                        {translations.cancel}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {translations.submit}
                    </Button>
                </DialogActions>
            </form>
        </Dialog>
    )
}
