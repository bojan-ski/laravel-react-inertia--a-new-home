import { useRef, type MouseEvent, type PointerEvent } from 'react'

const SWIPE_THRESHOLD = 50

export function useSwipe(onPrevious: () => void, onNext: () => void, enabled: boolean) {
    const startX = useRef<number | null>(null)
    const swiped = useRef(false)

    return {
        onPointerDown(event: PointerEvent<HTMLElement>) {
            startX.current = event.clientX
            swiped.current = false
        },
        onPointerUp(event: PointerEvent<HTMLElement>) {
            if (startX.current === null || !enabled) {
                return
            }

            const delta = event.clientX - startX.current
            startX.current = null

            if (delta > SWIPE_THRESHOLD) {
                swiped.current = true
                onPrevious()
            } else if (delta < -SWIPE_THRESHOLD) {
                swiped.current = true
                onNext()
            }
        },
        onPointerCancel() {
            startX.current = null
        },
        onClickCapture(event: MouseEvent<HTMLElement>) {
            if (swiped.current) {
                swiped.current = false
                event.preventDefault()
                event.stopPropagation()
            }
        },
    }
}
