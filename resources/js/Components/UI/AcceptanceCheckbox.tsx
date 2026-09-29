import Checkbox from '@/Components/UI/Checkbox'
import type { AcceptanceCheckboxProps } from '@/types/form'

export default function AcceptanceCheckbox({ id, checked, error, onChange, children }: AcceptanceCheckboxProps) {
    return (
        <div className="flex flex-col gap-1">
            <Checkbox
                id={id}
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                className="text-sm text-ink-muted"
            >
                {children}
            </Checkbox>

            {error && (
                <p id={`${id}-error`} className="text-[13px] text-ink">
                    {error}
                </p>
            )}
        </div>
    )
}
