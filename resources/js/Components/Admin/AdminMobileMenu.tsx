import { Link } from '@inertiajs/react'
import AdminNavLink from '@/Components/Admin/AdminNavLink'
import { isAdminNavItemActive } from '@/Components/Admin/adminNavItems'
import type { AdminMobileMenuProps } from '@/types/admin'

const secondaryRow =
    'flex min-h-13 w-full items-center border-b border-rule text-left text-[17px] text-ink-muted transition-colors duration-150 hover:text-forest'

export default function AdminMobileMenu({ id, items, currentPath, nav, signOutLabel, onClose }: AdminMobileMenuProps) {
    return (
        <nav id={id} aria-label={nav.aria_label} className="border-b border-rule bg-paper lg:hidden">
            <div className="flex flex-col px-5 pb-8 md:px-10">
                {items.map((item) => (
                    <AdminNavLink
                        key={item.key}
                        item={item}
                        active={isAdminNavItemActive(item.href, currentPath)}
                        pendingTemplate={nav.pending_count}
                        className="min-h-13 border-b border-rule text-[17px]"
                        onNavigate={onClose}
                    />
                ))}

                <Link href="/" onClick={onClose} className={secondaryRow}>
                    {nav.view_site}
                    <span aria-hidden="true">&nbsp;↗</span>
                </Link>
                <Link href="/logout" method="post" as="button" onClick={onClose} className={secondaryRow}>
                    {signOutLabel}
                </Link>
            </div>
        </nav>
    )
}
