import { Head } from '@inertiajs/react'
import ClosingCta from '@/Components/Landing/ClosingCta'
import Features from '@/Components/Landing/Features'
import Hero from '@/Components/Landing/Hero'
import HowItWorks from '@/Components/Landing/HowItWorks'
import PublicLayout from '@/Layouts/PublicLayout'
import type { HomePageProps } from '@/types/home'

export default function Home({ landing }: HomePageProps) {
    return (
        <PublicLayout>
            <Head title={landing.title} />

            <Hero content={landing.hero} />
            <Features content={landing.features} />
            <HowItWorks content={landing.how_it_works} />
            <ClosingCta content={landing.cta} />
        </PublicLayout>
    )
}
