import { Head } from '@inertiajs/react'
import PublicLayout from '@/Layouts/PublicLayout'
import type { StaticPageProps } from '@/types/staticPage'

export default function Show({ page }: StaticPageProps) {
    return (
        <PublicLayout>
            <Head title={page.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto max-w-3xl px-6 py-14 md:py-20">
                    <p className="text-xs font-medium uppercase tracking-wider text-accent">{page.eyebrow}</p>
                    <h1 className="mt-3 font-display text-4xl font-semibold text-text sm:text-5xl">{page.title}</h1>
                    <p className="mt-4 text-lg leading-relaxed text-text-soft">{page.lead}</p>
                    {page.updated && <p className="mt-6 text-sm text-text-muted">{page.updated}</p>}
                </div>
            </div>

            <div className="mx-auto flex max-w-3xl flex-col gap-12 px-6 py-14 md:py-16">
                {page.sections.map((section) => (
                    <section key={section.heading}>
                        <h2 className="font-display text-2xl font-semibold text-text">{section.heading}</h2>

                        <div className="mt-4 flex flex-col gap-4">
                            {section.paragraphs.map((paragraph) => (
                                <p key={paragraph} className="leading-relaxed text-text-soft">
                                    {paragraph}
                                </p>
                            ))}
                        </div>
                    </section>
                ))}
            </div>
        </PublicLayout>
    )
}
