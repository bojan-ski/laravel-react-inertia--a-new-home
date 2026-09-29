import Footer from '@/Components/Footer'
import Navbar from '@/Components/Navbar'
import type { PublicLayoutProps } from '@/types/layout'

export default function PublicLayout({ children, back, footer = true }: PublicLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col bg-paper text-ink">
            <Navbar back={back} />

            <main className="flex-1">{children}</main>

            {footer && <Footer />}
        </div>
    )
}
