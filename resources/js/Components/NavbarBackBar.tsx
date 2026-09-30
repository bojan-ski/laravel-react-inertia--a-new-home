import { Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import type { NavbarBackBarProps } from '@/types/navigation'
import type { SharedProps } from '@/types/shared'

export default function NavbarBackBar({ href, label, action, className = '' }: NavbarBackBarProps) {
    const { nav } = usePage<SharedProps>().props

    return (
        <div className={`grid grid-cols-[44px_1fr_44px] items-center py-2 pr-3 pl-2 ${className}`}>
            <Link
                href={href}
                aria-label={label}
                className="flex size-11 items-center justify-center text-ink transition-colors duration-150 hover:text-forest"
            >
                <ArrowLeft className="size-5" strokeWidth={1.25} aria-hidden="true" />
            </Link>
            <div className="text-center">
                <Link
                    href="/"
                    className="text-[15px] font-medium tracking-[0.04em] text-ink transition-colors duration-150"
                >
                    {nav.brand}
                </Link>
            </div>
            {action ?? <span aria-hidden="true" />}
        </div>
    )
}
