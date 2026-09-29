import { useCallback, useRef } from 'react'
import { useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import { useFocusTrap } from '@/Components/UI/useFocusTrap'
import type { ListingReportReasonValue, ReportListingDialogProps } from '@/types/listing-report'

export default function ReportListingDialog({ open, onClose, listingId, translations }: ReportListingDialogProps) {
    const firstReasonRef = useRef<HTMLInputElement>(null)
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<{
        reason: ListingReportReasonValue | ''
        details: string
    }>({ reason: '', details: '' })

    const handleClose = useCallback(() => {
        reset()
        clearErrors()
        onClose()
    }, [reset, clearErrors, onClose])

    const panelRef = useFocusTrap(open, handleClose, firstReasonRef)

    if (!open) {
        return null
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault()

        post(`/listings/${listingId}/report`, {
            preserveScroll: true,
            onSuccess: () => {
                reset()
                onClose()
            },
        })
    }

    const dialog = translations.dialog
    const reasons = Object.entries(translations.reasons) as [ListingReportReasonValue, string][]
    const detailsRequired = data.reason === 'other'
    const submitDisabled = processing || data.reason === '' || (detailsRequired && data.details.trim() === '')

    return (
        <div
            className="fixed inset-0 z-60 flex items-center justify-center bg-black/50 p-4"
            onClick={handleClose}
        >
            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby="report-listing-dialog-title"
                className="max-h-full w-full max-w-lg overflow-y-auto rounded-xl border border-border bg-surface p-6 shadow-xl"
                onClick={(event) => event.stopPropagation()}
            >
                <h2 id="report-listing-dialog-title" className="font-display text-2xl font-semibold text-text">
                    {dialog.title}
                </h2>

                <form onSubmit={handleSubmit} className="mt-5">
                    <fieldset>
                        <legend className="text-sm font-medium text-text">{dialog.reason_label}</legend>
                        <div className="mt-2 flex flex-col gap-2">
                            {reasons.map(([value, label], index) => (
                                <label
                                    key={value}
                                    className={`flex items-center gap-3 rounded-lg border px-3 py-2.5 text-sm transition-colors ${
                                        data.reason === value
                                            ? 'border-brand bg-brand-soft/40 text-text'
                                            : 'border-border text-text-soft hover:border-border-strong'
                                    }`}
                                >
                                    <input
                                        ref={index === 0 ? firstReasonRef : undefined}
                                        type="radio"
                                        name="reason"
                                        value={value}
                                        checked={data.reason === value}
                                        onChange={() => setData('reason', value)}
                                        className="size-4 accent-brand"
                                    />
                                    {label}
                                </label>
                            ))}
                        </div>
                        {errors.reason && <p className="mt-1.5 text-sm text-status-rejected">{errors.reason}</p>}
                    </fieldset>

                    <label htmlFor="report-listing-details" className="mt-5 block text-sm font-medium text-text">
                        {detailsRequired ? dialog.details_label_required : dialog.details_label}
                    </label>
                    <textarea
                        id="report-listing-details"
                        value={data.details}
                        onChange={(event) => setData('details', event.target.value)}
                        placeholder={dialog.details_placeholder}
                        required={detailsRequired}
                        rows={4}
                        maxLength={2000}
                        className="mt-1.5 w-full resize-y rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text outline-none transition-colors focus:border-brand"
                    />
                    {errors.details && <p className="mt-1.5 text-sm text-status-rejected">{errors.details}</p>}

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
                            {dialog.submit}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    )
}
