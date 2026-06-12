import Footer from '@/Components/Footer'
import ListingsNavbar from '@/Components/Listings/ListingsNavbar'
import type { ListingsLayoutProps } from '@/types/listing'

export default function ListingsLayout({ nav, children }: ListingsLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col bg-background text-text">
            <ListingsNavbar nav={nav} />

            <main className="flex-1">{children}</main>

            <Footer />
        </div>
    )
}
