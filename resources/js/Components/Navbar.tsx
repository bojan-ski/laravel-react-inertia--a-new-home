import { useState } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { Menu, X } from 'lucide-react'
import NavbarAuthActions from '@/Components/NavbarAuthActions'
import NavbarMobilePanel from '@/Components/NavbarMobilePanel'
import type { SharedProps } from '@/types/shared'
import type { NavLink } from '@/types/navigation'

export default function Navbar() {
    const page = usePage<SharedProps>()
    const { nav } = page.props
    const currentPath = page.url
    const [open, setOpen] = useState(false)

    const links: NavLink[] = [
        { label: nav.features, href: '#features', internal: false },
        { label: nav.how_it_works, href: '#how-it-works', internal: false },
        { label: nav.listings, href: '/listings', internal: true },
    ]

    return (
        <header className="border-b border-border bg-surface-muted">
            <div className="relative mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <Link href="/" className="font-display text-xl font-semibold text-text">
                    {nav.brand}
                </Link>

                <nav className="absolute left-1/2 hidden -translate-x-1/2 items-center gap-8 md:flex">
                    {links.map((link) => {
                        const isActive = link.internal && currentPath.startsWith(link.href)
                        const className = `text-sm font-medium transition-colors hover:text-text ${
                            isActive ? 'text-text' : 'text-text-soft'
                        }`

                        return link.internal ? (
                            <Link key={link.href} href={link.href} className={className}>
                                {link.label}
                            </Link>
                        ) : (
                            <a key={link.href} href={link.href} className={className}>
                                {link.label}
                            </a>
                        )
                    })}
                </nav>

                <div className="hidden items-center gap-5 md:flex">
                    <NavbarAuthActions />
                </div>

                <button
                    type="button"
                    onClick={() => setOpen((value) => !value)}
                    className="text-text md:hidden"
                    aria-label={open ? nav.close_menu : nav.open_menu}
                    aria-expanded={open}
                >
                    {open ? <X className="size-6" /> : <Menu className="size-6" />}
                </button>
            </div>

            {open && (
                <NavbarMobilePanel links={links} currentPath={currentPath} onClose={() => setOpen(false)} />
            )}
        </header>
    )
}
