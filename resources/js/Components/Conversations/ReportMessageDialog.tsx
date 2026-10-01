import { useCallback, useRef } from 'react'
import { useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import Button from '@/Components/UI/Button'
import Dialog, { DialogActions } from '@/Components/UI/Dialog'
import TextAreaField from '@/Components/UI/TextAreaField'
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
        <Dialog open={open} onClose={handleClose} title={translations.title} initialFocusRef={textareaRef}>
            <blockquote className="border-l border-rule pl-3.5 text-sm/normal wrap-break-word text-ink/75">
                {preview}
            </blockquote>

            <form onSubmit={handleSubmit} className="flex flex-col gap-5.5">
                <TextAreaField
                    ref={textareaRef}
                    id="report-reason"
                    aria-required
                    label={translations.prompt}
                    value={data.reason}
                    onChange={(event) => setData('reason', event.target.value)}
                    placeholder={translations.placeholder}
                    rows={4}
                    error={errors.reason}
                />

                <DialogActions>
                    <Button variant="secondary" onClick={handleClose} disabled={processing}>
                        {translations.cancel}
                    </Button>
                    <Button type="submit" disabled={submitDisabled}>
                        {translations.submit}
                    </Button>
                </DialogActions>
            </form>
        </Dialog>
    )
}
