import { useState } from 'react'
import { Eye, EyeOff } from 'lucide-react'
import TextField from '@/Components/UI/TextField'
import type { PasswordFieldProps } from '@/types/form'

export default function PasswordField({ showLabel, hideLabel, ...props }: PasswordFieldProps) {
    const [visible, setVisible] = useState(false)
    const Icon = visible ? EyeOff : Eye

    return (
        <TextField
            {...props}
            type={visible ? 'text' : 'password'}
            trailing={
                <button
                    type="button"
                    onClick={() => setVisible((current) => !current)}
                    aria-label={visible ? hideLabel : showLabel}
                    className="flex size-11 shrink-0 items-center justify-center text-ink-muted transition-colors duration-150 hover:text-ink"
                >
                    <Icon className="size-4.5" strokeWidth={1.25} aria-hidden="true" />
                </button>
            }
        />
    )
}
