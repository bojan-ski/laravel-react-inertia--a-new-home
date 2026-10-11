import { useEffect, useRef } from 'react'

export type RevealDirection = 'up' | 'left'

const STAGGER_MS = 70
const MAX_STAGGER_STEPS = 6

export function prefersReducedMotion(): boolean {
    return typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function byDocumentOrder(first: IntersectionObserverEntry, second: IntersectionObserverEntry): number {
    return first.target.compareDocumentPosition(second.target) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1
}

export default function useReveal<T extends HTMLElement>(direction: RevealDirection, items: unknown) {
    const ref = useRef<T>(null)

    useEffect(() => {
        const container = ref.current

        if (!container) {
            return
        }

        const pending = Array.from(container.children).filter(
            (child): child is HTMLElement => child instanceof HTMLElement && !child.hasAttribute('data-revealed'),
        )

        if (prefersReducedMotion()) {
            pending.forEach((element) => element.setAttribute('data-revealed', ''))

            return
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries
                    .filter((entry) => entry.isIntersecting)
                    .sort(byDocumentOrder)
                    .forEach((entry, index) => {
                        const element = entry.target as HTMLElement

                        element.style.setProperty('--reveal-delay', `${Math.min(index, MAX_STAGGER_STEPS) * STAGGER_MS}ms`)
                        element.setAttribute('data-revealed', '')
                        observer.unobserve(element)
                    })
            },
            { threshold: 0.1 },
        )

        pending.forEach((element) => observer.observe(element))

        return () => observer.disconnect()
    }, [items])

    return { ref, 'data-reveal': direction }
}
