import { usePage } from '@inertiajs/react'
import Footer from '@/Components/Footer'
import ListingsNavbar from '@/Components/Listings/ListingsNavbar'
import type { ListingsLayoutProps } from '@/types/listing'
import type { SharedProps } from '@/types/shared'

export default function ListingsLayout({ children }: ListingsLayoutProps) {
    const { listingsNav } = usePage<SharedProps>().props

    return (
        <div className="flex min-h-screen flex-col bg-background text-text">
            <ListingsNavbar nav={listingsNav} />

            <main className="flex-1">{children}</main>

            <Footer />
        </div>
    )
}
