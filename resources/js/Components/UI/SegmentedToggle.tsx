import type { SegmentedToggleProps } from '@/types/ui'

const sizeClasses = {
    sm: 'gap-4.5 text-sm',
    bar: 'gap-4.5 text-[15px]',
    md: 'gap-6 text-base',
    wide: 'gap-6 text-[15px]',
    spread: 'justify-between gap-2 text-sm *:inline-flex *:min-h-10 *:items-center',
}

const toneClasses = {
    default: {
        label: 'has-focus-visible:outline-forest',
        selected: 'border-forest text-forest',
        idle: 'border-transparent text-ink-muted hover:text-ink',
    },
    'on-photo': {
        label: 'pt-2.5 has-focus-visible:outline-paper max-lg:text-[15px] lg:pt-0',
        selected: 'border-paper text-paper',
        idle: 'border-transparent text-paper/75 hover:text-paper',
    },
}

export default function SegmentedToggle<T extends string>({
    name,
    legend,
    options,
    value,
    onChange,
    size = 'md',
    tone = 'default',
    className = '',
}: SegmentedToggleProps<T>) {
    const palette = toneClasses[tone]

    return (
        <fieldset className={`min-w-0 ${className}`}>
            <legend className="sr-only">{legend}</legend>
            <div className={`flex flex-wrap items-center ${sizeClasses[size]}`}>
                {options.map((option) => {
                    const selected = option.value === value

                    return (
                        <label
                            key={option.value}
                            className={`cursor-pointer border-b pb-1 transition-colors duration-150 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 ${palette.label} ${
                                selected ? palette.selected : palette.idle
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
