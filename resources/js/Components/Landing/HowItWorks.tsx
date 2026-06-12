import type { HowItWorksProps } from '@/types/home'

export default function HowItWorks({ content }: HowItWorksProps) {
    return (
        <section id="how-it-works" className="bg-background py-24 md:py-32">
            <div className="mx-auto max-w-7xl px-6">
                <p className="text-sm font-medium uppercase tracking-[0.2em] text-text-muted">
                    {content.eyebrow}
                </p>

                <h2 className="mt-4 font-display text-4xl font-semibold leading-tight text-text sm:text-5xl">
                    {content.heading_line1}
                    <br />
                    {content.heading_line2}
                </h2>

                <div className="mt-16 grid grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-4">
                    {content.steps.map((step) => (
                        <div key={step.number} className="border-t border-border pt-6">
                            <span className="font-display text-6xl font-semibold text-border-strong">
                                {step.number}
                            </span>
                            <h3 className="mt-4 text-lg font-bold text-text">{step.title}</h3>
                            <p className="mt-2 text-sm leading-relaxed text-text-soft">
                                {step.description}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    )
}
