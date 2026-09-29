import { Link, usePage } from '@inertiajs/react'
import Footer from '@/Components/Footer'
import ListingsNavbar from '@/Components/Listings/ListingsNavbar'
import type { AdminLayoutProps } from '@/types/layout'
import type { SharedProps } from '@/types/shared'

function isLinkActive(href: string, currentPath: string): boolean {
    if (href === '/admin') {
        return currentPath === '/admin'
    }

    return currentPath === href || currentPath.startsWith(`${href}/`)
}

export default function AdminLayout({ children }: AdminLayoutProps) {
    const page = usePage<SharedProps>()
    const { adminNav, listingsNav } = page.props
    const currentPath = page.url

    const links = [
        { label: adminNav.dashboard, href: '/admin' },
        { label: adminNav.listings, href: '/admin/listings' },
        { label: adminNav.agencies, href: '/admin/agencies' },
        { label: adminNav.users, href: '/admin/users' },
        { label: adminNav.reported_listings, href: '/admin/listing-reports' },
        { label: adminNav.reported_messages, href: '/admin/reported-messages' },
        { label: adminNav.contact_messages, href: '/admin/contact-messages' },
    ]

    return (
        <div className="flex min-h-screen flex-col bg-background text-text">
            <ListingsNavbar nav={listingsNav} />

            <main className="flex-1">
                <div className="mx-auto max-w-7xl px-6 py-10 md:py-14">
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-[240px_1fr]">
                        <aside className="lg:sticky lg:top-8 lg:self-start">
                            <nav className="rounded-2xl border border-border bg-surface p-4">
                                <p className="px-3 pb-3 text-xs font-medium uppercase tracking-wider text-text-muted">
                                    {adminNav.eyebrow}
                                </p>
                                <ul className="flex flex-col gap-1">
                                    {links.map((link) => {
                                        const active = isLinkActive(link.href, currentPath)

                                        return (
                                            <li key={link.href}>
                                                <Link
                                                    href={link.href}
                                                    className={`block border-l-2 px-3 py-2 text-sm transition-colors ${
                                                        active
                                                            ? 'border-brand font-semibold text-text'
                                                            : 'border-transparent text-text-soft hover:text-text'
                                                    }`}
                                                >
                                                    {link.label}
                                                </Link>
                                            </li>
                                        )
                                    })}
                                </ul>
                            </nav>
                        </aside>

                        <div>{children}</div>
                    </div>
                </div>
            </main>

            <Footer />
        </div>
    )
}
