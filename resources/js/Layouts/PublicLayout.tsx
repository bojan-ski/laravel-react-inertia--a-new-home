import { usePage } from '@inertiajs/react'
import Footer from '@/Components/Footer'
import Navbar from '@/Components/Navbar'
import SkipLink from '@/Components/UI/SkipLink'
import type { PublicLayoutProps } from '@/types/layout'
import type { SharedProps } from '@/types/shared'

const fillClasses = {
    always: 'h-dvh overflow-hidden',
    desktop: 'min-h-screen lg:h-dvh lg:min-h-0 lg:overflow-hidden',
}

export default function PublicLayout({
    children,
    back,
    backAction,
    footer = true,
    navVariant = 'default',
    fill,
    contained = false,
}: PublicLayoutProps) {
    const { nav } = usePage<SharedProps>().props

    return (
        <div className={`relative flex flex-col bg-paper text-ink ${fill ? fillClasses[fill] : 'min-h-screen'}`}>
            <SkipLink label={nav.skip_to_content} />
            <Navbar back={back} backAction={backAction} variant={navVariant} ruled={fill !== undefined} />

            <main
                id="main"
                tabIndex={-1}
                className={`focus-visible:outline-none ${fill ? 'flex min-h-0 flex-1 flex-col' : 'flex-1'} ${contained ? 'mx-auto w-full max-w-360' : ''}`}
            >
                {children}
            </main>

            {footer && <Footer />}
        </div>
    )
}
