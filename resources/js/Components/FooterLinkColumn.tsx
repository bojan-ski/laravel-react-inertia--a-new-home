import { Link } from '@inertiajs/react'
import Kicker from '@/Components/UI/Kicker'
import type { FooterLinkColumnProps } from '@/types/navigation'

const linkClassName =
    'flex min-h-8 items-center text-sm text-ink transition-colors duration-150 hover:text-forest lg:min-h-0'

export default function FooterLinkColumn({ heading, links }: FooterLinkColumnProps) {
    return (
        <div className="flex flex-col gap-2.5">
            <Kicker as="h2" className="max-lg:text-[11px] lg:tracking-[0.12em]">
                {heading}
            </Kicker>

            <ul className="flex flex-col lg:gap-2.5">
                {links.map((link) => (
                    <li key={link.label}>
                        {link.external ? (
                            <a href={link.href} target="_blank" rel="noopener noreferrer" className={linkClassName}>
                                {link.label}
                            </a>
                        ) : (
                            <Link href={link.href} className={linkClassName}>
                                {link.label}
                            </Link>
                        )}
                    </li>
                ))}
            </ul>
        </div>
    )
}
