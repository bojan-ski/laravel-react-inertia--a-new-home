import { Link } from '@inertiajs/react'
import { ArrowRight } from 'lucide-react'
import type { ClosingCtaProps } from '@/types/home'

export default function ClosingCta({ content }: ClosingCtaProps) {
    return (
        <section className="bg-surface-muted py-20 md:py-24">
            <div className="mx-auto flex max-w-7xl flex-col gap-10 px-6 md:flex-row md:items-center md:justify-between">
                <div className="max-w-xl">
                    <h2 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                        {content.heading}
                    </h2>
                    <p className="mt-3 text-base leading-relaxed text-text-soft">
                        {content.paragraph}
                    </p>
                </div>

                <div className="flex flex-wrap gap-4">
                    <Link
                        href="/listings"
                        className="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-3 text-base font-medium text-white transition-colors hover:bg-brand-dark"
                    >
                        {content.browse}
                        <ArrowRight className="size-4" />
                    </Link>
                    <Link
                        href="/register"
                        className="inline-flex items-center rounded-full border border-border-strong bg-surface px-6 py-3 text-base font-medium text-text transition-colors hover:bg-background"
                    >
                        {content.list_property}
                    </Link>
                </div>
            </div>
        </section>
    )
}
