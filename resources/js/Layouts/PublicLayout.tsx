import Footer from '@/Components/Footer'
import Navbar from '@/Components/Navbar'
import type { PublicLayoutProps } from '@/types/layout'

export default function PublicLayout({ children }: PublicLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col bg-background text-text">
            <Navbar />

            <main className="flex-1">{children}</main>

            <Footer />
        </div>
    )
}
