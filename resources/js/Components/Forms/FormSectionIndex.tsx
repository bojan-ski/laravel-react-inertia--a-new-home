import useScrollSpy from '@/Components/UI/useScrollSpy'
import type { FormSectionIndexProps } from '@/types/form'

export default function FormSectionIndex({ sections, label }: FormSectionIndexProps) {
    const activeId = useScrollSpy(sections.map(({ id }) => id))

    return (
        <nav aria-label={label} className="sticky top-8 hidden flex-col gap-0.5 self-start pt-12 xl:flex">
            {sections.map((section, index) => {
                const current = section.id === activeId

                return (
                    <a
                        key={section.id}
                        href={`#${section.id}`}
                        aria-current={current ? 'location' : undefined}
                        className={`flex gap-3.5 py-2.5 text-sm transition-colors duration-150 ${
                            current ? 'text-forest' : 'text-ink-muted hover:text-forest'
                        }`}
                    >
                        <span className="w-5 shrink-0 tabular-nums">{String(index + 1).padStart(2, '0')}</span>
                        {section.label}
                    </a>
                )
            })}
        </nav>
    )
}
