import { Head, usePage } from '@inertiajs/react'
import ClosingCta from '@/Components/Landing/ClosingCta'
import Features from '@/Components/Landing/Features'
import Hero from '@/Components/Landing/Hero'
import HowItWorks from '@/Components/Landing/HowItWorks'
import FlashBanner from '@/Components/UI/FlashBanner'
import PublicLayout from '@/Layouts/PublicLayout'
import type { HomePageProps } from '@/types/home'
import type { SharedProps } from '@/types/shared'

export default function Home({ landing }: HomePageProps) {
    const { flash } = usePage<SharedProps>().props

    return (
        <PublicLayout>
            <Head title={landing.title} />

            {flash.success && (
                <div className="mx-auto max-w-7xl px-6 pt-6">
                    <FlashBanner message={flash.success} variant="success" className="mb-6" />
                </div>
            )}

            <Hero content={landing.hero} />
            <Features content={landing.features} />
            <HowItWorks content={landing.how_it_works} />
            <ClosingCta content={landing.cta} />
        </PublicLayout>
    )
}
