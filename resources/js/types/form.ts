import type { InputHTMLAttributes, ReactNode } from 'react'

export interface TextFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    id: string
    label: string
    error?: string
}

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

export interface AcceptanceCheckboxProps {
    id: string
    checked: boolean
    error?: string
    onChange: (checked: boolean) => void
    children: ReactNode
}
