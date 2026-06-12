import { useEffect, useRef, useState } from 'react'
import FeatureLabelList from '@/Components/Landing/FeatureLabelList'
import type { FeaturesProps } from '@/types/home'

export default function Features({ content }: FeaturesProps) {
    const sectionRef = useRef<HTMLElement>(null)
    const videoRef = useRef<HTMLVideoElement>(null)
    const [activeIndex, setActiveIndex] = useState(0)
    const count = content.items.length

    useEffect(() => {
        function handleScroll() {
            const section = sectionRef.current
            if (!section) {
                return
            }

            const scrollable = section.offsetHeight - window.innerHeight
            const scrolled = Math.min(Math.max(-section.getBoundingClientRect().top, 0), scrollable)
            const progress = scrollable > 0 ? scrolled / scrollable : 0

            setActiveIndex(Math.min(count - 1, Math.floor(progress * count)))
        }

        handleScroll()
        window.addEventListener('scroll', handleScroll, { passive: true })

        return () => window.removeEventListener('scroll', handleScroll)
    }, [count])

    useEffect(() => {
        const video = videoRef.current
        if (!video) {
            return
        }

        const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')

        function applyMotionPreference() {
            if (motionQuery.matches) {
                video!.pause()
            } else {
                void video!.play().catch(() => {})
            }
        }

        applyMotionPreference()
        motionQuery.addEventListener('change', applyMotionPreference)

        return () => motionQuery.removeEventListener('change', applyMotionPreference)
    }, [])

    return (
        <section ref={sectionRef} id="features" className="relative h-[400vh]">
            <div className="sticky top-0 h-screen overflow-hidden bg-surface-dark">
                <video
                    ref={videoRef}
                    className="absolute inset-0 h-full w-full object-cover"
                    src="/videos/features.mp4"
                    autoPlay
                    loop
                    muted
                    playsInline
                />
                <div className="absolute inset-0 bg-black/65" />

                <div className="relative mx-auto flex h-full max-w-7xl flex-col justify-center px-6 md:px-12">
                    <p className="mb-10 text-sm font-medium uppercase tracking-[0.2em] text-white/50">
                        {content.eyebrow}
                    </p>

                    <FeatureLabelList items={content.items} activeIndex={activeIndex} />
                </div>

                <div className="absolute bottom-12 left-6 h-px w-64 bg-white/30 md:left-12" />
            </div>
        </section>
    )
}
