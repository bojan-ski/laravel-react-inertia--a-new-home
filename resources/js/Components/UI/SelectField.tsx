import { ChevronDown } from 'lucide-react'
import FieldShell from '@/Components/UI/FieldShell'
import type { SelectFieldProps } from '@/types/form'

export default function SelectField({ id, label, error, children, ...props }: SelectFieldProps) {
    return (
        <FieldShell id={id} label={label} error={error}>
            <div
                className={`relative flex items-center border-ink transition-colors duration-150 focus-within:border-forest ${
                    error ? 'border-b-2' : 'border-b'
                }`}
            >
                <select
                    id={id}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className="min-h-12 w-full min-w-0 appearance-none bg-transparent py-0 pr-7 pl-0 text-[17px] font-light text-ink"
                    {...props}
                >
                    {children}
                </select>
                <ChevronDown
                    className="pointer-events-none absolute right-0 size-4 text-ink"
                    strokeWidth={1.25}
                    aria-hidden="true"
                />
            </div>
        </FieldShell>
    )
}
