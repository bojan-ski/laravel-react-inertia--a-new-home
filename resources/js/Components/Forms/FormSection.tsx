import type { FormSectionProps } from '@/types/form'

export default function FormSection({ id, number, title, note, children }: FormSectionProps) {
    const headingId = `${id}-heading`

    return (
        <section
            id={id}
            aria-labelledby={headingId}
            className="flex scroll-mt-8 flex-col gap-5.5 border-t border-rule py-8 lg:grid lg:grid-cols-[300px_minmax(0,1fr)] lg:gap-12 lg:py-12"
        >
            <div className="flex flex-col gap-5.5 lg:gap-2.5">
                <div className="flex items-baseline gap-3 lg:flex-col lg:items-start lg:gap-2.5">
                    <span className="text-xs text-forest-700 tabular-nums">{number}</span>
                    <h2
                        id={headingId}
                        className="text-2xl font-light tracking-[-0.02em] lg:text-[30px] lg:leading-[1.1]"
                    >
                        {title}
                    </h2>
                </div>

                {note && (
                    <p className="-mt-2.5 text-[13px] leading-normal text-ink-muted lg:mt-0 lg:text-sm lg:leading-[1.55]">
                        {note}
                    </p>
                )}
            </div>

            {children && (
                <div className="grid min-w-0 grid-cols-2 gap-x-4 gap-y-5.5 lg:gap-x-8 lg:gap-y-7">{children}</div>
            )}
        </section>
    )
}
