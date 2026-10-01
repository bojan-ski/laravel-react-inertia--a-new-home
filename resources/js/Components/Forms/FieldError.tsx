import type { FieldErrorProps } from '@/types/form'

export default function FieldError({ id, message }: FieldErrorProps) {
    return message ? (
        <p id={id} className="text-[13px] text-ink">
            {message}
        </p>
    ) : null
}
