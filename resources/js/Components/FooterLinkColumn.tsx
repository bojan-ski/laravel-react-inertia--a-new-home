import { Link } from '@inertiajs/react'
import type { FooterLinkColumnProps } from '@/types/navigation'

export default function FooterLinkColumn({ heading, links }: FooterLinkColumnProps) {
    return (
        <div className="flex flex-col gap-4">
            <h3 className="text-xs font-medium uppercase tracking-wider text-text-inverse/50">
                {heading}
            </h3>

            <ul className="flex flex-col gap-3">
                {links.map((link) => (
                    <li key={link.label}>
                        {link.external ? (
                            <a
                                href={link.href}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-sm text-text-inverse/70 transition-colors hover:text-text-inverse"
                            >
                                {link.label}
                            </a>
                        ) : (
                            <Link
                                href={link.href}
                                className="text-sm text-text-inverse/70 transition-colors hover:text-text-inverse"
                            >
                                {link.label}
                            </Link>
                        )}
                    </li>
                ))}
            </ul>
        </div>
    )
}
