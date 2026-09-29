import FieldShell from '@/Components/UI/FieldShell'
import type { TextAreaFieldProps } from '@/types/form'

export default function TextAreaField({ id, label, error, ...props }: TextAreaFieldProps) {
    return (
        <FieldShell id={id} label={label} error={error}>
            <textarea
                id={id}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                className={`w-full bg-transparent px-4 py-3.5 text-base/[1.6] font-book text-ink transition-colors duration-150 placeholder:text-ink-faint focus:border-forest ${
                    error ? 'border-2 border-ink' : 'border border-ink-faint'
                }`}
                {...props}
            />
        </FieldShell>
    )
}
