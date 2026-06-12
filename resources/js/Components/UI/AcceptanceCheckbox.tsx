import type { AcceptanceCheckboxProps } from '@/types/form'

export default function AcceptanceCheckbox({ id, checked, error, onChange, children }: AcceptanceCheckboxProps) {
    return (
        <div className="flex flex-col gap-1">
            <label htmlFor={id} className="flex items-start gap-2.5 text-sm text-text-soft">
                <input
                    id={id}
                    type="checkbox"
                    checked={checked}
                    onChange={(e) => onChange(e.target.checked)}
                    className="mt-0.5 h-4 w-4 rounded border-border text-brand focus:ring-brand"
                />
                <span>{children}</span>
            </label>

            {error && <p className="text-sm text-status-rejected">{error}</p>}
        </div>
    )
}
