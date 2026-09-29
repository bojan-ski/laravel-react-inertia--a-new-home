import { useCallback, useRef } from 'react'
import { useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import Button from '@/Components/UI/Button'
import Dialog, { DialogActions } from '@/Components/UI/Dialog'
import TextAreaField from '@/Components/UI/TextAreaField'
import type { RejectAgencyDialogProps } from '@/types/admin'

export default function RejectAgencyDialog({ open, onClose, actionUrl, subjectName, dialog }: RejectAgencyDialogProps) {
    const textareaRef = useRef<HTMLTextAreaElement>(null)
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({ reason: '' })

    const handleClose = useCallback(() => {
        reset()
        clearErrors()
        onClose()
    }, [reset, clearErrors, onClose])

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
        <Dialog
            open={open}
            onClose={handleClose}
            title={dialog.title}
            description={subjectName}
            initialFocusRef={textareaRef}
        >
            <form onSubmit={handleSubmit} className="flex flex-col gap-5.5">
                <div className="flex flex-col gap-1.5">
                    <TextAreaField
                        ref={textareaRef}
                        id="reject-agency-reason"
                        label={dialog.reason_label}
                        value={data.reason}
                        onChange={(event) => setData('reason', event.target.value)}
                        placeholder={dialog.reason_placeholder}
                        rows={4}
                        error={errors.reason}
                    />
                    <p className="text-[13px] text-ink-muted">{dialog.reason_helper}</p>
                </div>

                <DialogActions>
                    <Button variant="secondary" onClick={handleClose} disabled={processing}>
                        {dialog.cancel}
                    </Button>
                    <Button type="submit" variant="ink-outline" disabled={submitDisabled}>
                        {dialog.reject}
                    </Button>
                </DialogActions>
            </form>
        </Dialog>
    )
}
