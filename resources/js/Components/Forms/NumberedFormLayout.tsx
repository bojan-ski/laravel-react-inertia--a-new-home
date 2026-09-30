import FormSectionIndex from '@/Components/Forms/FormSectionIndex'
import type { NumberedFormLayoutProps } from '@/types/form'

export default function NumberedFormLayout({ sections, indexLabel, children, bar }: NumberedFormLayoutProps) {
    return (
        <>
            <div className="px-5 py-6 md:px-10 lg:pt-0 lg:pb-12 xl:grid xl:grid-cols-[220px_minmax(0,1fr)] xl:gap-16 xl:px-16">
                <FormSectionIndex sections={sections} label={indexLabel} />
                <div className="min-w-0">{children}</div>
            </div>

            {bar}
        </>
    )
}
