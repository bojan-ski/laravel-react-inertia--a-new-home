import type { ChipToggleProps } from '@/types/ui'

export default function ChipToggle({ type, name, value, label, checked, onChange }: ChipToggleProps) {
    return (
        <label
            className={`inline-flex cursor-pointer items-center border px-3.5 py-2.25 text-sm transition-colors duration-150 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-forest ${
                checked ? 'border-forest bg-forest-50 text-forest-800' : 'border-rule text-ink hover:border-ink'
            }`}
        >
            <input
                type={type}
                name={name}
                value={value}
                checked={checked}
                onChange={(event) => onChange(event.target.checked, event)}
                className="sr-only"
            />
            {label}
        </label>
    )
}
