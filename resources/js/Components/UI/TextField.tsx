import FieldShell from '@/Components/UI/FieldShell'
import type { TextFieldProps } from '@/types/form'

export default function TextField({ id, label, error, trailing, ...props }: TextFieldProps) {
    return (
        <FieldShell id={id} label={label} error={error}>
            <div
                className={`flex items-center border-ink transition-colors duration-150 focus-within:border-forest ${
                    error ? 'border-b-2' : 'border-b'
                }`}
            >
                <input
                    id={id}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className="min-h-12 w-full min-w-0 bg-transparent px-2 text-[17px] font-light text-ink placeholder:text-ink-faint"
                    {...props}
                />
                {trailing}
            </div>
        </FieldShell>
    )
}
