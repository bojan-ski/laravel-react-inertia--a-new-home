import type { ListingStatusValue } from '@/types/listing'

export interface StatusBadgeProps {
    status: ListingStatusValue
    label: string
}

export interface ConfirmDialogProps {
    open: boolean
    title: string
    description: string
    confirmLabel: string
    cancelLabel: string
    processing: boolean
    onConfirm: () => void
    onCancel: () => void
}
