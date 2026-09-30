import { useId } from 'react'
import type { AccountTypeFieldProps } from '@/types/form'

export default function AccountTypeField({ label, options, value, error, onChange }: AccountTypeFieldProps) {
    const id = useId()
    const labelId = `${id}-label`
    const errorId = `${id}-error`

    return (
        <div
            role="group"
            aria-labelledby={labelId}
            aria-describedby={error ? errorId : undefined}
            className="flex flex-col gap-2.5"
        >
            <span id={labelId} className="text-xs tracking-[0.04em] text-ink-muted">
                {label}
            </span>

            <div className="grid gap-2.5 sm:grid-cols-2">
                {options.map((option) => {
                    const isActive = value === option.value

                    return (
                        <button
                            key={option.value}
                            type="button"
                            aria-pressed={isActive}
                            onClick={() => onChange(option.value)}
                            className={`flex flex-col gap-1.5 border p-4 text-left transition-colors duration-150 ${
                                isActive ? 'border-forest bg-forest-50' : 'border-rule hover:bg-ink/4'
                            }`}
                        >
                            <span className="text-base font-medium text-ink">{option.label}</span>
                            <span className="text-[13px] leading-[1.45] text-ink-muted">{option.description}</span>
                        </button>
                    )
                })}
            </div>

            {error && (
                <p id={errorId} className="text-[13px] text-ink">
                    {error}
                </p>
            )}
        </div>
    )
}
