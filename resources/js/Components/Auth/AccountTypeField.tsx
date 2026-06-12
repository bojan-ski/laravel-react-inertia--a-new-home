import type { AccountTypeFieldProps } from '@/types/form'

export default function AccountTypeField({ label, options, value, error, onChange }: AccountTypeFieldProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <span className="text-sm font-medium text-text-soft">{label}</span>

            <div className="grid gap-3 sm:grid-cols-2">
                {options.map((option) => {
                    const isActive = value === option.value

                    return (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => onChange(option.value)}
                            className={`rounded-lg border p-4 text-left transition-colors ${
                                isActive
                                    ? 'border-brand bg-brand-soft'
                                    : 'border-border bg-surface hover:border-border-strong'
                            }`}
                        >
                            <span className="block text-sm font-semibold text-text">{option.label}</span>
                            <span className="mt-1 block text-xs text-text-soft">{option.description}</span>
                        </button>
                    )
                })}
            </div>

            {error && <p className="text-sm text-status-rejected">{error}</p>}
        </div>
    )
}
