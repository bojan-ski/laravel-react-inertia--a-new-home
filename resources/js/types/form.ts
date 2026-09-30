import type { InputHTMLAttributes, ReactNode, Ref, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react'

export interface TextFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    ref?: Ref<HTMLInputElement>
    id: string
    label: string
    error?: string
    trailing?: ReactNode
}

export interface FieldShellProps {
    id: string
    label: string
    error?: string
    children: ReactNode
}

export interface PasswordFieldProps extends Omit<TextFieldProps, 'type' | 'trailing'> {
    showLabel: string
    hideLabel: string
}

export interface SelectFieldProps extends SelectHTMLAttributes<HTMLSelectElement> {
    id: string
    label: string
    error?: string
    children: ReactNode
}

export interface TextAreaFieldProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    ref?: Ref<HTMLTextAreaElement>
    id: string
    label: string
    error?: string
}

export interface CheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'className' | 'children'> {
    ref?: Ref<HTMLInputElement>
    className?: string
    children?: ReactNode
}

export type RadioProps = CheckboxProps

export interface AccountTypeOption {
    value: string
    label: string
    description: string
}

export interface AccountTypeFieldProps {
    label: string
    options: AccountTypeOption[]
    value: string
    error?: string
    onChange: (value: string) => void
}

export interface HoneypotFieldProps {
    value: string
    error?: string
    onChange: (value: string) => void
}

export interface FieldErrorProps {
    message?: string
}

export interface FormSectionLink {
    id: string
    label: string
}

export interface NumberedFormLayoutProps {
    sections: FormSectionLink[]
    indexLabel: string
    children: ReactNode
    bar: ReactNode
}

export interface FormSectionIndexProps {
    sections: FormSectionLink[]
    label: string
}

export interface FormSectionProps {
    id: string
    number: string
    title: string
    note?: string
    children?: ReactNode
}

export interface FormActionBarProps {
    note: string
    submitLabel: string
    processing: boolean
    cancelHref?: string
    cancelLabel?: string
}

export interface AcceptanceCheckboxProps {
    id: string
    checked: boolean
    error?: string
    onChange: (checked: boolean) => void
    children: ReactNode
}
