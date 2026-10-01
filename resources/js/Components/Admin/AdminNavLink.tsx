import { Link } from '@inertiajs/react'
import type { AdminNavLinkProps } from '@/types/admin'

export default function AdminNavLink({ item, active, pendingTemplate, className = '', onNavigate }: AdminNavLinkProps) {
    const showCount = item.count !== undefined && item.count > 0

    return (
        <Link
            href={item.href}
            onClick={onNavigate}
            aria-current={active ? 'page' : undefined}
            className={`flex items-center justify-between gap-3 transition-colors duration-150 hover:text-forest ${
                active ? 'text-forest' : 'text-ink'
            } ${className}`}
        >
            <span>{item.label}</span>
            {showCount && (
                <>
                    <span
                        aria-hidden="true"
                        className={`min-w-5.5 px-1.5 py-0.5 text-center text-xs ${
                            active ? 'bg-forest text-paper' : 'bg-sand text-ink'
                        }`}
                    >
                        {item.count}
                    </span>
                    <span className="sr-only">{pendingTemplate.replace(':count', String(item.count))}</span>
                </>
            )}
        </Link>
    )
}
