import { useCallback, useRef } from 'react'
import { useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import { useFocusTrap } from '@/Components/UI/useFocusTrap'
import type { RejectAgencyDialogProps } from '@/types/admin'

export default function RejectAgencyDialog({ open, onClose, actionUrl, subjectName, dialog }: RejectAgencyDialogProps) {
    const textareaRef = useRef<HTMLTextAreaElement>(null)
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({ reason: '' })

    const handleClose = useCallback(() => {
        reset()
        clearErrors()
        onClose()
    }, [reset, clearErrors, onClose])

    const panelRef = useFocusTrap(open, handleClose, textareaRef)

    if (!open) {
        return null
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault()

        post(actionUrl, {
            preserveScroll: true,
            onSuccess: () => {
                reset()
                onClose()
            },
        })
    }

    const submitDisabled = processing || data.reason.trim() === ''

    return (
        <div
            className="fixed inset-0 z-60 flex items-center justify-center bg-black/50 p-4"
            onClick={handleClose}
        >
            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby="reject-agency-dialog-title"
                className="w-full max-w-lg rounded-xl border border-border bg-surface p-6 shadow-xl"
                onClick={(event) => event.stopPropagation()}
            >
                <h2 id="reject-agency-dialog-title" className="font-display text-2xl font-semibold text-text">
                    {dialog.title}
                </h2>
                <p className="mt-2 text-sm text-text-soft">
                    <span className="font-semibold text-text">{subjectName}</span>
                </p>

                <form onSubmit={handleSubmit} className="mt-5">
                    <label htmlFor="reject-agency-reason" className="block text-sm font-medium text-text">
                        {dialog.reason_label}
                    </label>
                    <textarea
                        id="reject-agency-reason"
                        ref={textareaRef}
                        value={data.reason}
                        onChange={(event) => setData('reason', event.target.value)}
                        placeholder={dialog.reason_placeholder}
                        rows={4}
                        className="mt-1.5 w-full resize-y rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text outline-none transition-colors focus:border-brand"
                    />
                    {errors.reason && (
                        <p className="mt-1.5 text-sm text-status-rejected">{errors.reason}</p>
                    )}
                    <p className="mt-1.5 text-xs text-text-soft">{dialog.reason_helper}</p>

                    <div className="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={handleClose}
                            disabled={processing}
                            className="rounded-lg border border-border-strong px-4 py-2 text-sm font-medium text-text-soft transition-colors hover:border-text hover:text-text disabled:opacity-50"
                        >
                            {dialog.cancel}
                        </button>
                        <button
                            type="submit"
                            disabled={submitDisabled}
                            className="rounded-lg bg-status-rejected px-4 py-2 text-sm font-semibold text-text-inverse transition-colors hover:opacity-90 disabled:opacity-50"
                        >
                            {dialog.reject}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    )
}
