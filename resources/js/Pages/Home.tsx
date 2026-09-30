import { Head, usePage } from '@inertiajs/react'
import { useState } from 'react'
import ClosingCta from '@/Components/Landing/ClosingCta'
import Features from '@/Components/Landing/Features'
import Hero from '@/Components/Landing/Hero'
import Intro from '@/Components/Landing/Intro'
import LatestHomes from '@/Components/Landing/LatestHomes'
import PhotoPair from '@/Components/Landing/PhotoPair'
import FlashBanner from '@/Components/UI/FlashBanner'
import PublicLayout from '@/Layouts/PublicLayout'
import type { HomePageProps } from '@/types/home'
import type { SharedProps } from '@/types/shared'

export function HomeFlashNotice() {
    const { flash, ui } = usePage<SharedProps>().props
    const message = flash.success
    const [dismissed, setDismissed] = useState<string | null>(null)

    if (!message || dismissed === message) {
        return null
    }

    return (
        <div role="status" className="fixed inset-x-4 top-4 z-50 mx-auto max-w-130 shadow-lg">
            <FlashBanner
                message={message}
                variant="success"
                className=""
                onDismiss={() => setDismissed(message)}
                dismissLabel={ui.dismiss}
            />
        </div>
    )
}

export default function Home({ landing, latestListings }: HomePageProps) {
    return (
        <PublicLayout navVariant="on-photo">
            <Head title={landing.title} />

            <HomeFlashNotice />

            <Hero content={landing.hero} />
            <Intro content={landing.intro} />
            <PhotoPair />
            <Features content={landing.features} />
            <LatestHomes content={landing.latest} listings={latestListings} />
            <ClosingCta content={landing.cta} />
        </PublicLayout>
    )
}
