import { Head } from '@inertiajs/react'
import PublicLayout from '@/Layouts/PublicLayout'
import SectionDivider from '@/Components/UI/SectionDivider'
import type { HomePageProps } from '@/types/home'

export default function Home({ title, heading, subheading, stack }: HomePageProps) {
    return (
        <PublicLayout>
            <Head title={title} />

            <section className="mx-auto max-w-3xl px-6 py-24 text-center">
                <h1 className="font-display text-5xl font-bold text-text">{heading}</h1>

                <p className="mt-6 text-lg text-text-soft">{subheading}</p>

                <SectionDivider className="my-12" />

                <p className="text-sm text-text-muted">{stack}</p>
            </section>
        </PublicLayout>
    )
}
