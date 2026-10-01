import NavbarAuthActions from '@/Components/NavbarAuthActions'
import NavbarLink from '@/Components/NavbarLink'
import { isNavItemActive } from '@/Components/navItems'
import type { NavbarMobilePanelProps } from '@/types/navigation'

export default function NavbarMobilePanel({ id, label, items, currentPath, user, onClose }: NavbarMobilePanelProps) {
    return (
        <div id={id} className="border-t border-rule bg-paper lg:hidden">
            <nav aria-label={label} className="mx-auto flex max-w-360 flex-col px-5 pb-8 md:px-10">
                {items.map((item) => (
                    <NavbarLink
                        key={item.key}
                        item={item}
                        active={isNavItemActive(item, currentPath)}
                        className="min-h-13 border-b border-rule text-[17px]"
                        onNavigate={onClose}
                    />
                ))}

                <NavbarAuthActions user={user} variant="panel" onNavigate={onClose} />
            </nav>
        </div>
    )
}
