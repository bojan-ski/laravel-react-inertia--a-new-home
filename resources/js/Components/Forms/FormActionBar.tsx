import Button from '@/Components/UI/Button'
import type { FormActionBarProps } from '@/types/form'

export default function FormActionBar({ note, submitLabel, processing, cancelHref, cancelLabel }: FormActionBarProps) {
    return (
        <div className="sticky bottom-0 z-10 flex flex-col-reverse gap-2 border-t border-rule bg-paper px-5 pt-3 pb-4 md:flex-row md:items-center md:justify-between md:gap-6 md:px-10 md:py-5 xl:px-16">
            <p className="text-xs text-ink-muted md:text-sm">{note}</p>

            <div className="flex shrink-0 gap-2.5">
                {cancelHref && cancelLabel && (
                    <div className="hidden md:flex">
                        <Button href={cancelHref} variant="secondary" className="min-h-13">
                            {cancelLabel}
                        </Button>
                    </div>
                )}
                <Button
                    type="submit"
                    fullWidth
                    disabled={processing}
                    className="min-h-13 md:w-auto md:gap-12"
                    trailing={<span aria-hidden="true">→</span>}
                >
                    {submitLabel}
                </Button>
            </div>
        </div>
    )
}
