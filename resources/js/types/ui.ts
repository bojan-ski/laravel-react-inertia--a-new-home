import type { AnchorHTMLAttributes, ButtonHTMLAttributes, ChangeEvent, ReactNode, Ref, RefObject } from 'react'
import type { InertiaLinkProps } from '@inertiajs/react'
import type { UserStatusValue } from '@/types/admin'
import type { AgencyStatusValue } from '@/types/agency'
import type { ListingStatusValue } from '@/types/listing'

export type BadgeStatusValue = ListingStatusValue | UserStatusValue | AgencyStatusValue | 'expired'

export interface StatusBadgeProps {
    status: BadgeStatusValue
    label: string
}

interface FlashBannerBaseProps {
    message: string
    variant: 'success' | 'info' | 'error'
    className?: string
}

export type FlashBannerProps = FlashBannerBaseProps &
    ({ onDismiss?: undefined; dismissLabel?: undefined } | { onDismiss: () => void; dismissLabel: string })

export interface DialogProps {
    open: boolean
    onClose: () => void
    title: string
    description?: ReactNode
    size?: 'confirm' | 'form'
    initialFocusRef?: RefObject<HTMLElement | null>
    children: ReactNode
}

export interface DialogActionsProps {
    children: ReactNode
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
    tone?: 'danger' | 'brand'
}

export type ButtonVariant =
    | 'primary'
    | 'secondary'
    | 'link'
    | 'strong-link'
    | 'ink-outline'
    | 'on-photo'
    | 'on-photo-outline'

export type ButtonSize = 'sm' | 'md' | 'lg'

interface ButtonBaseProps {
    variant?: ButtonVariant
    size?: ButtonSize
    fullWidth?: boolean
    trailing?: ReactNode
    className?: string
    children: ReactNode
}

export type NativeButtonProps = ButtonBaseProps &
    Omit<ButtonHTMLAttributes<HTMLButtonElement>, keyof ButtonBaseProps> & {
        href?: undefined
        external?: undefined
        ref?: Ref<HTMLButtonElement>
    }

export type InertiaButtonLinkProps = ButtonBaseProps &
    Omit<InertiaLinkProps, keyof ButtonBaseProps | 'href'> & {
        href: string
        external?: false
    }

export type ExternalButtonLinkProps = ButtonBaseProps &
    Omit<AnchorHTMLAttributes<HTMLAnchorElement>, keyof ButtonBaseProps | 'href'> & {
        href: string
        external: true
    }

export type ButtonProps = NativeButtonProps | InertiaButtonLinkProps | ExternalButtonLinkProps

export interface ToggleOption<T extends string = string> {
    value: T
    label: string
}

export interface SegmentedToggleProps<T extends string = string> {
    name: string
    legend: string
    options: ToggleOption<T>[]
    value: T
    onChange: (value: T) => void
    size?: 'sm' | 'md'
    tone?: 'default' | 'on-photo'
    className?: string
}

export interface ChipToggleProps {
    type: 'checkbox' | 'radio'
    name: string
    value: string
    label: string
    checked: boolean
    onChange: (checked: boolean, event: ChangeEvent<HTMLInputElement>) => void
}

export interface EmptyStateProps {
    title: string
    hint?: string
    action?: ReactNode
    className?: string
}

export interface KickerProps {
    as?: 'span' | 'p' | 'h2'
    size?: 'sm' | 'md'
    className?: string
    children: ReactNode
}

export interface LogoTileProps {
    name: string
    size?: 'md' | 'lg' | 'xl'
}
