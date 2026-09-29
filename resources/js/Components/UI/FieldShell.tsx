import type { FieldShellProps } from '@/types/form'

export default function FieldShell({ id, label, error, children }: FieldShellProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <label htmlFor={id} className="text-xs tracking-[0.04em] text-ink-muted">
                {label}
            </label>

            {children}

            {error && (
                <p id={`${id}-error`} className="text-[13px] text-ink">
                    {error}
                </p>
            )}
        </div>
    )
}
