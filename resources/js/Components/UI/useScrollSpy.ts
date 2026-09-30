import { useEffect, useState } from 'react'

const BAND_TOP = 40

export default function useScrollSpy(ids: string[]): string | undefined {
    const idList = ids.join(' ')
    const [activeId, setActiveId] = useState(ids[0])

    useEffect(() => {
        const sectionIds = idList.split(' ')
        const elements = sectionIds
            .map((id) => document.getElementById(id))
            .filter((element): element is HTMLElement => element !== null)
        const inView = new Set<string>()

        function update() {
            const atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2

            if (atBottom) {
                setActiveId(sectionIds[sectionIds.length - 1])

                return
            }

            const beforeFirst = (elements[0]?.getBoundingClientRect().top ?? 0) > BAND_TOP
            const current = beforeFirst ? sectionIds[0] : sectionIds.findLast((id) => inView.has(id))

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
    }, [idList])

    return activeId
}
