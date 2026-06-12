import type { TextFieldProps } from '@/types/form'

export default function TextField({ id, label, error, ...props }: TextFieldProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <label htmlFor={id} className="text-sm font-medium text-text-soft">
                {label}
            </label>

            <input
                id={id}
                className="w-full rounded-lg border border-border bg-surface px-4 py-3 text-text outline-none transition-colors focus:border-brand"
                {...props}
            />

            {error && <p className="text-sm text-status-rejected">{error}</p>}
        </div>
    )
}
