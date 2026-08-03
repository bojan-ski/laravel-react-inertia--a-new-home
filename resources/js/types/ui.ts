import type { UserStatusValue } from '@/types/admin'
import type { ListingStatusValue } from '@/types/listing'

export type BadgeStatusValue = ListingStatusValue | UserStatusValue

export interface StatusBadgeProps {
    status: BadgeStatusValue
    label: string
}

export interface FlashBannerProps {
    message: string
    variant: 'success' | 'error'
    coloredText?: boolean
    className?: string
    onDismiss?: () => void
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
