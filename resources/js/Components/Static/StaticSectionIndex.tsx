import useScrollSpy from '@/Components/UI/useScrollSpy'
import type { StaticSectionIndexProps } from '@/types/staticPage'

export default function StaticSectionIndex({ sections, label }: StaticSectionIndexProps) {
    const activeId = useScrollSpy(sections.map(({ id }) => id))

    return (
        <nav
            aria-label={label}
            className="sticky top-8 hidden flex-col gap-0.5 self-start border-t border-ink pt-4 lg:flex"
        >
            {sections.map((section) => {
                const current = section.id === activeId

                return (
                    <a
                        key={section.id}
                        href={`#${section.id}`}
                        aria-current={current ? 'location' : undefined}
                        className={`py-2 text-sm transition-colors duration-150 ${
                            current ? 'text-forest' : 'text-ink-muted hover:text-forest'
                        }`}
                    >
                        {section.label}
                    </a>
                )
            })}
        </nav>
    )
}
