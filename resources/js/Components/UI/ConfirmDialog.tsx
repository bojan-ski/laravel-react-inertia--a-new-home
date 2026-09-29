import { useRef } from 'react'
import Button from '@/Components/UI/Button'
import Dialog, { DialogActions } from '@/Components/UI/Dialog'
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
    tone = 'danger',
}: ConfirmDialogProps) {
    const cancelRef = useRef<HTMLButtonElement>(null)

    return (
        <Dialog
            open={open}
            onClose={onCancel}
            title={title}
            description={description}
            size="confirm"
            initialFocusRef={cancelRef}
        >
            <DialogActions>
                <Button ref={cancelRef} variant="secondary" onClick={onCancel} disabled={processing}>
                    {cancelLabel}
                </Button>
                <Button variant={tone === 'brand' ? 'primary' : 'ink-outline'} onClick={onConfirm} disabled={processing}>
                    {confirmLabel}
                </Button>
            </DialogActions>
        </Dialog>
    )
}
