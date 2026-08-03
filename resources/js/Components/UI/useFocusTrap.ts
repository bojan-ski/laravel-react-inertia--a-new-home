import { useEffect, useRef, type RefObject } from 'react'

export function useFocusTrap<T extends HTMLElement = HTMLElement>(
    open: boolean,
    onClose: () => void,
    initialFocusRef?: RefObject<T | null>,
): RefObject<HTMLDivElement | null> {
    const panelRef = useRef<HTMLDivElement>(null)

    useEffect(() => {
        if (!open) {
            return
        }

        initialFocusRef?.current?.focus()

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                onClose()
                return
            }

            if (event.key !== 'Tab' || !panelRef.current) {
                return
            }

            const focusable = panelRef.current.querySelectorAll<HTMLElement>(
                'button:not([disabled]), [href], textarea, input, [tabindex]:not([tabindex="-1"])',
            )

            if (focusable.length === 0) {
                return
            }

            const first = focusable[0]
            const last = focusable[focusable.length - 1]

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault()
                last.focus()
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault()
                first.focus()
            }
        }

        document.addEventListener('keydown', handleKeyDown)

        return () => document.removeEventListener('keydown', handleKeyDown)
    }, [open, onClose, initialFocusRef])

    return panelRef
}
