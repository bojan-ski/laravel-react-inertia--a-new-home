import { useEffect, useId, useRef, useState } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { Equal, X } from 'lucide-react'
import AdminMobileMenu from '@/Components/Admin/AdminMobileMenu'
import AdminNavLink from '@/Components/Admin/AdminNavLink'
import { buildAdminNavItems, isAdminNavItemActive } from '@/Components/Admin/adminNavItems'
import Kicker from '@/Components/UI/Kicker'
import SkipLink from '@/Components/UI/SkipLink'
import type { AdminLayoutProps } from '@/types/layout'
import type { SharedProps } from '@/types/shared'

export default function AdminLayout({ children }: AdminLayoutProps) {
    const page = usePage<SharedProps>()
    const { adminNav, adminCounts, nav } = page.props
    const currentPath = page.url
    const items = buildAdminNavItems(adminNav, adminCounts)
    const [open, setOpen] = useState(false)
    const menuButtonRef = useRef<HTMLButtonElement>(null)
    const menuId = useId()

    useEffect(() => {
        if (!open) {
            return
        }

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false)
                menuButtonRef.current?.focus()
            }
        }

        document.addEventListener('keydown', handleKeyDown)

        return () => document.removeEventListener('keydown', handleKeyDown)
    }, [open])

    return (
        <div className="min-h-screen bg-paper text-ink lg:grid lg:grid-cols-[272px_minmax(0,1fr)]">
            <SkipLink label={nav.skip_to_content} />
            <aside className="sticky top-0 hidden h-dvh flex-col gap-10 overflow-y-auto border-r border-rule py-9 pl-10 lg:flex">
                <div className="flex flex-col items-start gap-1.5">
                    <Link
                        href="/admin"
                        className="text-[17px] font-medium tracking-[0.04em] transition-colors duration-150 hover:text-forest"
                    >
                        {nav.brand}
                    </Link>
                    <Kicker size="sm" className="tracking-[0.16em]">
                        {adminNav.eyebrow}
                    </Kicker>
                </div>

                <nav aria-label={adminNav.aria_label} className="flex flex-col">
                    {items.map((item) => {
                        const active = isAdminNavItemActive(item.href, currentPath)

                        return (
                            <AdminNavLink
                                key={item.key}
                                item={item}
                                active={active}
                                pendingTemplate={adminNav.pending_count}
                                className={`py-2.75 pr-6 text-[15px] ${active ? 'border-r-2 border-forest' : ''}`}
                            />
                        )
                    })}
                </nav>

                <div className="mt-auto flex flex-col items-start gap-2.5 pr-6 text-[13px] text-ink-muted">
                    <Link href="/" className="transition-colors duration-150 hover:text-forest">
                        {adminNav.view_site}
                        <span aria-hidden="true"> ↗</span>
                    </Link>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="transition-colors duration-150 hover:text-forest"
                    >
                        {nav.sign_out}
                    </Link>
                </div>
            </aside>

            <header className="lg:hidden">
                <div className="flex items-center justify-between border-b border-rule py-2 pr-3 pl-5 md:pl-10">
                    <div className="flex items-baseline gap-2.5">
                        <Link href="/admin" className="text-[15px] font-medium tracking-[0.04em]">
                            {nav.brand}
                        </Link>
                        <Kicker className="text-[10px]! tracking-[0.16em]">{adminNav.eyebrow}</Kicker>
                    </div>

                    <button
                        ref={menuButtonRef}
                        type="button"
                        onClick={() => setOpen((value) => !value)}
                        className="flex size-11 items-center justify-center text-ink"
                        aria-label={open ? nav.close_menu : nav.open_menu}
                        aria-expanded={open}
                        aria-controls={menuId}
                    >
                        {open ? (
                            <X className="size-5.5" strokeWidth={1.25} aria-hidden="true" />
                        ) : (
                            <Equal className="size-5.5" strokeWidth={1.25} aria-hidden="true" />
                        )}
                    </button>
                </div>

                {open && (
                    <AdminMobileMenu
                        id={menuId}
                        items={items}
                        currentPath={currentPath}
                        nav={adminNav}
                        signOutLabel={nav.sign_out}
                        onClose={() => setOpen(false)}
                    />
                )}
            </header>

            <main id="main" tabIndex={-1} className="min-w-0 px-5 pt-7 pb-10 focus-visible:outline-none md:px-10 lg:px-16 lg:pt-14 lg:pb-16">
                <div className="flex max-w-292 flex-col gap-7 lg:gap-10">{children}</div>
            </main>
        </div>
    )
}
