import { useRef } from 'react'
import { useFocusTrap } from '@/Components/UI/useFocusTrap'
import type { ConfirmDialogProps } from '@/types/ui'

export default function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel,
    cancelLabel,
    processing,
    onConfirm,
    onCancel,
}: ConfirmDialogProps) {
    const cancelRef = useRef<HTMLButtonElement>(null)
    const panelRef = useFocusTrap(open, onCancel, cancelRef)

    if (!open) {
        return null
    }

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            onClick={onCancel}
        >
            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-dialog-title"
                className="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-xl"
                onClick={(event) => event.stopPropagation()}
            >
                <h2 id="confirm-dialog-title" className="font-display text-2xl font-semibold text-text">
                    {title}
                </h2>
                <p className="mt-3 text-sm leading-relaxed text-text-soft">{description}</p>

                <div className="mt-6 flex justify-end gap-3">
                    <button
                        ref={cancelRef}
                        type="button"
                        onClick={onCancel}
                        disabled={processing}
                        className="rounded-lg border border-border-strong px-4 py-2 text-sm font-medium text-text-soft transition-colors hover:border-text hover:text-text disabled:opacity-50"
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                        className="rounded-lg bg-status-rejected px-4 py-2 text-sm font-semibold text-text-inverse transition-colors hover:opacity-90 disabled:opacity-50"
                    >
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    )
}
