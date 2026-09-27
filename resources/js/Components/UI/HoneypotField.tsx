import type { HoneypotFieldProps } from '@/types/form'

export default function HoneypotField({ value, error, onChange }: HoneypotFieldProps) {
    return (
        <>
            <div aria-hidden="true" className="sr-only">
                <input
                    type="text"
                    name="website"
                    tabIndex={-1}
                    autoComplete="off"
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                />
            </div>
            {error && (
                <p role="alert" className="text-sm text-status-rejected">
                    {error}
                </p>
            )}
        </>
    )
}
