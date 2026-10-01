import { useCallback, useRef } from 'react'
import { useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import Button from '@/Components/UI/Button'
import Dialog, { DialogActions } from '@/Components/UI/Dialog'
import Radio from '@/Components/UI/Radio'
import TextAreaField from '@/Components/UI/TextAreaField'
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
        <Dialog open={open} onClose={handleClose} title={dialog.title} initialFocusRef={firstReasonRef}>
            <form onSubmit={handleSubmit} className="flex flex-col gap-5.5">
                <fieldset
                    aria-describedby={errors.reason ? 'report-listing-reason-error' : undefined}
                    className="flex flex-col gap-1.5"
                >
                    <legend className="mb-1.5 text-xs tracking-[0.04em] text-ink-muted">{dialog.reason_label}</legend>
                    <div className="flex flex-col">
                        {reasons.map(([value, label], index) => (
                            <Radio
                                key={value}
                                ref={index === 0 ? firstReasonRef : undefined}
                                name="reason"
                                value={value}
                                checked={data.reason === value}
                                onChange={() => setData('reason', value)}
                                className="min-h-10 py-2.5 text-[15px]"
                            >
                                {label}
                            </Radio>
                        ))}
                    </div>
                    {errors.reason && (
                        <p id="report-listing-reason-error" className="text-[13px] text-ink">
                            {errors.reason}
                        </p>
                    )}
                </fieldset>

                <TextAreaField
                    id="report-listing-details"
                    label={detailsRequired ? dialog.details_label_required : dialog.details_label}
                    value={data.details}
                    onChange={(event) => setData('details', event.target.value)}
                    placeholder={dialog.details_placeholder}
                    required={detailsRequired}
                    rows={3}
                    maxLength={2000}
                    error={errors.details}
                />

                <DialogActions>
                    <Button variant="secondary" onClick={handleClose} disabled={processing}>
                        {dialog.cancel}
                    </Button>
                    <Button type="submit" disabled={submitDisabled}>
                        {dialog.submit}
                    </Button>
                </DialogActions>
            </form>
        </Dialog>
    )
}
