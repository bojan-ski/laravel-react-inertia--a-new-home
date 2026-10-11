import { useEffect, useRef, useState } from 'react'
import { prefersReducedMotion } from '@/Components/UI/useReveal'

const DURATION_MS = 1000

export interface CountUpProps {
    value: string
}

export default function CountUp({ value }: CountUpProps) {
    const [, prefix = '', digits = '', suffix = ''] = /^(\D*)(\d+)(.*)$/.exec(value) ?? []
    const target = Number(digits)
    const ref = useRef<HTMLSpanElement>(null)
    const [current, setCurrent] = useState(target)

    useEffect(() => {
        const element = ref.current

        if (!element || prefersReducedMotion()) {
            return
        }

        let frame = 0
        setCurrent(0)

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (!entry?.isIntersecting) {
                    return
                }

                observer.disconnect()
                const start = performance.now()

                const tick = (now: number) => {
                    const progress = Math.min((now - start) / DURATION_MS, 1)

                    setCurrent(Math.round(target * (1 - (1 - progress) ** 3)))

                    if (progress < 1) {
                        frame = requestAnimationFrame(tick)
                    }
                }

                frame = requestAnimationFrame(tick)
            },
            { threshold: 0.5 },
        )

        observer.observe(element)

        return () => {
            observer.disconnect()
            cancelAnimationFrame(frame)
        }
    }, [target])

    if (digits === '') {
        return value
    }

    return (
        <>
            <span ref={ref} aria-hidden="true" className="tabular-nums">
                {prefix}
                {current}
                {suffix}
            </span>
            <span className="sr-only">{value}</span>
        </>
    )
}
