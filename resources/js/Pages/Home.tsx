import { Head } from '@inertiajs/react'
import ClosingCta from '@/Components/Landing/ClosingCta'
import Features from '@/Components/Landing/Features'
import Hero from '@/Components/Landing/Hero'
import Intro from '@/Components/Landing/Intro'
import LatestHomes from '@/Components/Landing/LatestHomes'
import PhotoPair from '@/Components/Landing/PhotoPair'
import PublicLayout from '@/Layouts/PublicLayout'
import type { HomePageProps } from '@/types/home'

export default function Home({ landing, latestListings }: HomePageProps) {
    return (
        <PublicLayout navVariant="on-photo">
            <Head title={landing.title} />

            <Hero content={landing.hero} />
            <Intro content={landing.intro} />
            <PhotoPair />
            <Features content={landing.features} />
            <LatestHomes content={landing.latest} listings={latestListings} />
            <ClosingCta content={landing.cta} />
        </PublicLayout>
    )
}
