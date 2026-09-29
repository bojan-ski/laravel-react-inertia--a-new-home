import type { RadioProps } from '@/types/form'

export default function Radio({ className = '', children, ...props }: RadioProps) {
    return (
        <label className={`inline-flex items-start gap-2.5 ${className}`}>
            <span className="relative mt-px flex size-4.5 shrink-0">
                <input
                    type="radio"
                    className="peer size-4.5 appearance-none rounded-full border border-ink bg-transparent disabled:cursor-not-allowed disabled:opacity-45"
                    {...props}
                />
                <span
                    className="pointer-events-none absolute inset-0 m-auto size-2.5 rounded-full bg-forest opacity-0 transition-opacity duration-150 peer-checked:opacity-100"
                    aria-hidden="true"
                />
            </span>
            {children && <span>{children}</span>}
        </label>
    )
}
