import { useCallback, useRef } from 'react'
import { useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import { useFocusTrap } from '@/Components/UI/useFocusTrap'
import type { ReportMessageDialogProps } from '@/types/reported-message'

export default function ReportMessageDialog({
    open,
    onClose,
    messageId,
    preview,
    translations,
}: ReportMessageDialogProps) {
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

        post(`/messages/${messageId}/report`, {
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
                aria-labelledby="report-dialog-title"
                className="w-full max-w-lg rounded-xl border border-border bg-surface p-6 shadow-xl"
                onClick={(event) => event.stopPropagation()}
            >
                <h2 id="report-dialog-title" className="font-display text-2xl font-semibold text-text">
                    {translations.title}
                </h2>

                <blockquote className="mt-4 border-l-4 border-status-rejected bg-surface-muted px-4 py-3 text-sm italic text-text-soft">
                    {preview}
                </blockquote>

                <form onSubmit={handleSubmit} className="mt-5">
                    <label htmlFor="report-reason" className="block text-sm font-medium text-text">
                        {translations.prompt}
                    </label>
                    <textarea
                        id="report-reason"
                        ref={textareaRef}
                        value={data.reason}
                        onChange={(event) => setData('reason', event.target.value)}
                        placeholder={translations.placeholder}
                        rows={4}
                        className="mt-1.5 w-full resize-y rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text outline-none transition-colors focus:border-brand"
                    />
                    {errors.reason && (
                        <p className="mt-1.5 text-sm text-status-rejected">{errors.reason}</p>
                    )}

                    <div className="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={handleClose}
                            disabled={processing}
                            className="rounded-lg border border-border-strong px-4 py-2 text-sm font-medium text-text-soft transition-colors hover:border-text hover:text-text disabled:opacity-50"
                        >
                            {translations.cancel}
                        </button>
                        <button
                            type="submit"
                            disabled={submitDisabled}
                            className="rounded-lg bg-status-rejected px-4 py-2 text-sm font-semibold text-text-inverse transition-colors hover:opacity-90 disabled:opacity-50"
                        >
                            {translations.submit}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    )
}
