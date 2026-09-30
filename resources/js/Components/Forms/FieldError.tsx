import type { FieldErrorProps } from '@/types/form'

export default function FieldError({ message }: FieldErrorProps) {
    return message ? <p className="text-[13px] text-ink">{message}</p> : null
}
