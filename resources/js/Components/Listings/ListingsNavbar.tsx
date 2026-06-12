import { useState } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { ChevronLeft, Menu, X } from 'lucide-react'
import ListingsNavbarAuthActions from '@/Components/Listings/ListingsNavbarAuthActions'
import ListingsNavbarMobilePanel from '@/Components/Listings/ListingsNavbarMobilePanel'
import type { ListingsNavbarProps, ListingsNavLink } from '@/types/listing'

export default function ListingsNavbar({ nav }: ListingsNavbarProps) {
    const currentPath = usePage().url
    const [open, setOpen] = useState(false)

    const centerLinks: ListingsNavLink[] = [
        { label: nav.listings, href: '/listings' },
        { label: nav.agencies, href: '/agencies' },
        { label: nav.favorites, href: '/favorites' },
    ]

    return (
        <header className="border-b border-border bg-surface">
            <div className="relative mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <div className="flex items-center gap-3 sm:gap-4">
                    <Link
                        href="/"
                        className="hidden items-center gap-1 text-sm font-medium text-text-soft transition-colors hover:text-text sm:flex"
                    >
                        <ChevronLeft className="size-4" />
                        {nav.back}
                    </Link>

                    <Link href="/" className="flex items-center gap-2">
                        <span className="flex size-8 items-center justify-center rounded-md bg-surface-dark text-xs font-bold text-text-inverse">
                            NB
                        </span>
                        <span className="font-display text-lg font-semibold text-text">{nav.brand}</span>
                    </Link>
                </div>

                <nav className="absolute left-1/2 hidden -translate-x-1/2 items-center gap-8 md:flex">
                    {centerLinks.map((link) => {
                        const isActive = currentPath.startsWith(link.href)

                        return (
                            <Link
                                key={link.href}
                                href={link.href}
                                className={`text-sm font-medium transition-colors hover:text-text ${
                                    isActive ? 'text-text underline underline-offset-4' : 'text-text-soft'
                                }`}
                            >
                                {link.label}
                            </Link>
                        )
                    })}
                </nav>

                <div className="hidden items-center gap-4 md:flex">
                    <ListingsNavbarAuthActions nav={nav} />
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
                <ListingsNavbarMobilePanel
                    nav={nav}
                    links={centerLinks}
                    currentPath={currentPath}
                    onClose={() => setOpen(false)}
                />
            )}
        </header>
    )
}
