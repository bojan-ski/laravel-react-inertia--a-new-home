import { useState } from 'react'
import { router } from '@inertiajs/react'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import type { ConfirmDeleteUserDialogProps } from '@/types/admin'

export default function ConfirmDeleteUserDialog({
    open,
    onClose,
    userId,
    userName,
    userEmail,
    translations,
}: ConfirmDeleteUserDialogProps) {
    const [processing, setProcessing] = useState(false)

    function handleConfirm() {
        router.delete(`/admin/users/${userId}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false)
                onClose()
            },
        })
    }

    return (
        <ConfirmDialog
            open={open}
            title={translations.confirm.title}
            description={translations.confirm.description
                .replace(':name', userName)
                .replace(':email', userEmail)}
            confirmLabel={translations.confirm.confirm}
            cancelLabel={translations.confirm.cancel}
            processing={processing}
            onConfirm={handleConfirm}
            onCancel={onClose}
        />
    )
}
