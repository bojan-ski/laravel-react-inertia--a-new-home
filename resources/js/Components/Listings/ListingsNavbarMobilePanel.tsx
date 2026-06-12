import { Link } from '@inertiajs/react'
import ListingsNavbarAuthActions from '@/Components/Listings/ListingsNavbarAuthActions'
import type { ListingsNavbarMobilePanelProps } from '@/types/listing'

export default function ListingsNavbarMobilePanel({
    nav,
    links,
    currentPath,
    onClose,
}: ListingsNavbarMobilePanelProps) {
    return (
        <div className="border-t border-border bg-surface md:hidden">
            <nav className="mx-auto flex max-w-7xl flex-col gap-1 px-6 py-4">
                {links.map((link) => {
                    const isActive = currentPath.startsWith(link.href)

                    return (
                        <Link
                            key={link.href}
                            href={link.href}
                            onClick={onClose}
                            className={`py-2 text-base font-medium transition-colors hover:text-text ${
                                isActive ? 'text-text' : 'text-text-soft'
                            }`}
                        >
                            {link.label}
                        </Link>
                    )
                })}

                <div className="mt-3 flex flex-col items-start gap-3 border-t border-border pt-4">
                    <ListingsNavbarAuthActions nav={nav} onNavigate={onClose} />
                </div>
            </nav>
        </div>
    )
}
