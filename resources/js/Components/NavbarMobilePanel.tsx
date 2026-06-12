import { Link } from '@inertiajs/react'
import NavbarAuthActions from '@/Components/NavbarAuthActions'
import type { NavbarMobilePanelProps } from '@/types/navigation'

export default function NavbarMobilePanel({ links, currentPath, onClose }: NavbarMobilePanelProps) {
    return (
        <div className="border-t border-border bg-surface-muted md:hidden">
            <nav className="mx-auto flex max-w-7xl flex-col gap-1 px-6 py-4">
                {links.map((link) => {
                    const isActive = link.internal && currentPath.startsWith(link.href)
                    const className = `py-2 text-base font-medium transition-colors hover:text-text ${
                        isActive ? 'text-text' : 'text-text-soft'
                    }`

                    return link.internal ? (
                        <Link key={link.href} href={link.href} onClick={onClose} className={className}>
                            {link.label}
                        </Link>
                    ) : (
                        <a key={link.href} href={link.href} onClick={onClose} className={className}>
                            {link.label}
                        </a>
                    )
                })}

                <div className="mt-3 flex flex-col items-start gap-3 border-t border-border pt-4">
                    <NavbarAuthActions onNavigate={onClose} />
                </div>
            </nav>
        </div>
    )
}
