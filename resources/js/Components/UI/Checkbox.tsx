import { Check } from 'lucide-react'
import type { CheckboxProps } from '@/types/form'

export default function Checkbox({ className = '', children, ...props }: CheckboxProps) {
    return (
        <label className={`inline-flex items-start gap-2.5 ${className}`}>
            <span className="relative mt-px flex size-4.5 shrink-0">
                <input
                    type="checkbox"
                    className="peer size-4.5 appearance-none border border-ink bg-transparent transition-colors duration-150 checked:border-forest checked:bg-forest disabled:cursor-not-allowed disabled:opacity-45"
                    {...props}
                />
                <Check
                    className="pointer-events-none absolute inset-0 m-auto size-3 text-white opacity-0 peer-checked:opacity-100"
                    strokeWidth={3}
                    aria-hidden="true"
                />
            </span>
            {children && <span>{children}</span>}
        </label>
    )
}
