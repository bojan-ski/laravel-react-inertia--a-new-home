import Footer from '@/Components/Footer'
import Navbar from '@/Components/Navbar'
import type { PublicLayoutProps } from '@/types/layout'

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
}: PublicLayoutProps) {
    return (
        <div className={`relative flex flex-col bg-paper text-ink ${fill ? fillClasses[fill] : 'min-h-screen'}`}>
            <Navbar back={back} backAction={backAction} variant={navVariant} ruled={fill !== undefined} />

            <main className={fill ? 'flex min-h-0 flex-1 flex-col' : 'flex-1'}>{children}</main>

            {footer && <Footer />}
        </div>
    )
}
