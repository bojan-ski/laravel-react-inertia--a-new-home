import { ChevronDown } from 'lucide-react'
import type { ListingTextSelectProps } from '@/types/listing'

export default function ListingTextSelect({
    id,
    label,
    value,
    onChange,
    options,
    active = false,
    className = '',
}: ListingTextSelectProps) {
    return (
        <div
            className={`relative inline-flex shrink-0 items-center transition-colors duration-150 hover:text-forest ${
                active ? 'text-forest' : ''
            } ${className}`}
        >
            <label htmlFor={id} className="sr-only">
                {label}
            </label>
            <select
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="min-h-11 cursor-pointer appearance-none bg-transparent pr-5 field-sizing-content"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            <ChevronDown
                className="pointer-events-none absolute right-0 size-3.5"
                strokeWidth={1.25}
                aria-hidden="true"
            />
        </div>
    )
}
