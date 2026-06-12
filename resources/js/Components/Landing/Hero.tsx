import { Link } from '@inertiajs/react'
import { ArrowRight } from 'lucide-react'
import { Fragment } from 'react'
import type { HeroProps } from '@/types/home'

export default function Hero({ content }: HeroProps) {
    return (
        <section className="relative flex min-h-[85vh] items-center justify-center overflow-hidden">
            <video
                className="absolute inset-0 h-full w-full object-cover"
                src="/videos/hero.mp4"
                autoPlay
                loop
                muted
                playsInline
            />
            <div className="absolute inset-0 bg-black/45" />

            <div className="relative mx-auto max-w-3xl px-6 py-24 text-center text-white">
                <h1 className="font-display text-5xl font-semibold leading-tight sm:text-6xl md:text-7xl">
                    {content.headline_line1}
                    <br />
                    {content.headline_line2}
                </h1>

                <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-white/80">
                    {content.subheading}
                </p>

                <div className="mt-10">
                    <Link
                        href="/listings"
                        className="inline-flex items-center gap-2 rounded-full bg-white px-8 py-3.5 text-base font-medium text-text transition-colors hover:bg-white/90"
                    >
                        {content.cta}
                        <ArrowRight className="size-4" />
                    </Link>
                </div>

                <div className="mt-16 flex items-center justify-center gap-6 sm:gap-10">
                    {content.stats.map((stat, index) => (
                        <Fragment key={stat.label}>
                            {index > 0 && <span className="h-10 w-px bg-white/25" />}
                            <div className="flex flex-col items-center">
                                <span className="font-display text-3xl font-semibold sm:text-4xl">
                                    {stat.value}
                                </span>
                                <span className="mt-1 text-sm text-white/70">{stat.label}</span>
                            </div>
                        </Fragment>
                    ))}
                </div>
            </div>
        </section>
    )
}
