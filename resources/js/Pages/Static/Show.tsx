import { Head } from '@inertiajs/react'
import StaticSectionIndex from '@/Components/Static/StaticSectionIndex'
import Kicker from '@/Components/UI/Kicker'
import PublicLayout from '@/Layouts/PublicLayout'
import type { StaticPageKey, StaticPageProps } from '@/types/staticPage'

const photos: Partial<Record<StaticPageKey, string>> = {
    about: '/images/hero-modern-house-white.webp',
}

export default function Show({ pageKey, page, translations }: StaticPageProps) {
    const photo = photos[pageKey]
    const sections = page.sections.map((section, index) => ({
        ...section,
        id: `section-${index + 1}`,
    }))

    return (
        <PublicLayout>
            <Head title={page.title} />

            <div className="mx-auto max-w-360 px-5 md:px-10 lg:px-16">
                <header className="flex flex-col gap-3.5 pt-9 pb-8 lg:grid lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-end lg:gap-16 lg:py-18">
                    <div className="flex flex-col gap-3.5 lg:gap-5">
                        <Kicker size="sm" className="lg:text-xs">
                            {page.eyebrow}
                        </Kicker>
                        <h1 className="text-[52px] leading-[0.92] font-extralight tracking-[-0.045em] wrap-break-word md:text-[80px] lg:text-[112px] lg:leading-[0.9]">
                            {page.title}
                        </h1>
                    </div>

                    <div className="flex flex-col gap-3.5 lg:pb-2.5">
                        <p className="text-[17px]/normal font-light lg:text-xl/normal">{page.lead}</p>
                        {page.updated && <p className="text-xs text-ink-muted lg:text-[13px]">{page.updated}</p>}
                    </div>
                </header>

                {photo && (
                    <div className="relative mb-12 aspect-4/3 overflow-hidden bg-sand md:aspect-video lg:mb-24 lg:aspect-auto lg:h-140">
                        <img
                            src={photo}
                            alt=""
                            width={2000}
                            height={1327}
                            className="absolute inset-0 size-full object-cover"
                        />
                    </div>
                )}

                <div className="pb-12 lg:grid lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-12 lg:pb-30 xl:grid-cols-[280px_minmax(0,1fr)] xl:gap-24">
                    <StaticSectionIndex
                        sections={sections.map(({ id, heading }) => ({ id, label: heading }))}
                        label={translations.index_label}
                    />

                    <div className="max-w-190">
                        {sections.map((section) => (
                            <section
                                key={section.id}
                                id={section.id}
                                aria-labelledby={`${section.id}-heading`}
                                className="flex scroll-mt-8 flex-col gap-3 border-t border-rule py-7 lg:gap-4.5 lg:py-10"
                            >
                                <h2
                                    id={`${section.id}-heading`}
                                    className="text-[26px] font-light tracking-[-0.02em] lg:text-4xl"
                                >
                                    {section.heading}
                                </h2>

                                {section.paragraphs.map((paragraph) => (
                                    <p
                                        key={paragraph}
                                        className="text-[15px]/[1.7] font-book lg:text-[17px]/[1.75] lg:text-ink/82"
                                    >
                                        {paragraph}
                                    </p>
                                ))}
                            </section>
                        ))}
                    </div>
                </div>
            </div>
        </PublicLayout>
    )
}
