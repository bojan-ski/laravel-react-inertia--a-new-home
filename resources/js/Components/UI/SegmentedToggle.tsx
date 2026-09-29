import type { SegmentedToggleProps } from '@/types/ui'

const sizeClasses = {
    sm: 'gap-4.5 text-sm',
    md: 'gap-6 text-base',
}

export default function SegmentedToggle<T extends string>({
    name,
    legend,
    options,
    value,
    onChange,
    size = 'md',
    className = '',
}: SegmentedToggleProps<T>) {
    return (
        <fieldset className={`min-w-0 ${className}`}>
            <legend className="sr-only">{legend}</legend>
            <div className={`flex flex-wrap items-center ${sizeClasses[size]}`}>
                {options.map((option) => {
                    const selected = option.value === value

                    return (
                        <label
                            key={option.value}
                            className={`cursor-pointer border-b pb-1 transition-colors duration-150 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-forest ${
                                selected ? 'border-forest text-forest' : 'border-transparent text-ink-muted hover:text-ink'
                            }`}
                        >
                            <input
                                type="radio"
                                name={name}
                                value={option.value}
                                checked={selected}
                                onChange={() => onChange(option.value)}
                                className="sr-only"
                            />
                            {option.label}
                        </label>
                    )
                })}
            </div>
        </fieldset>
    )
}
