import { useEffect, useState } from 'react'
import type { FormSectionIndexProps } from '@/types/form'

const BAND_TOP = 40

export default function FormSectionIndex({ sections, label }: FormSectionIndexProps) {
    const sectionIds = sections.map(({ id }) => id).join(' ')
    const [activeId, setActiveId] = useState(sections[0]?.id)

    useEffect(() => {
        const ids = sectionIds.split(' ')
        const elements = ids
            .map((id) => document.getElementById(id))
            .filter((element): element is HTMLElement => element !== null)
        const inView = new Set<string>()

        function update() {
            const atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2

            if (atBottom) {
                setActiveId(ids[ids.length - 1])

                return
            }

            const beforeFirst = (elements[0]?.getBoundingClientRect().top ?? 0) > BAND_TOP
            const current = beforeFirst ? ids[0] : ids.findLast((id) => inView.has(id))

            if (current) {
                setActiveId(current)
            }
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        inView.add(entry.target.id)
                    } else {
                        inView.delete(entry.target.id)
                    }
                })
                update()
            },
            { rootMargin: `-${BAND_TOP}px 0px -85% 0px` },
        )

        elements.forEach((element) => observer.observe(element))
        window.addEventListener('scroll', update, { passive: true })

        return () => {
            observer.disconnect()
            window.removeEventListener('scroll', update)
        }
    }, [sectionIds])

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
