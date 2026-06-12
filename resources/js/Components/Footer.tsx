import { Link, usePage } from '@inertiajs/react'
import FooterLinkColumn from '@/Components/FooterLinkColumn'
import type { FooterLink } from '@/types/navigation'
import type { SharedProps } from '@/types/shared'

export default function Footer() {
    const { footer } = usePage<SharedProps>().props
    const year = new Date().getFullYear()

    const company: FooterLink[] = [
        { label: footer.about, href: '#', external: false },
        { label: footer.careers, href: '#', external: false },
        { label: footer.press, href: '#', external: false },
        { label: footer.contact, href: '#', external: false },
    ]

    const legal: FooterLink[] = [
        { label: footer.privacy_policy, href: '#', external: false },
        { label: footer.terms_of_service, href: '#', external: false },
        { label: footer.cookie_policy, href: '#', external: false },
        { label: footer.gdpr, href: '#', external: false },
    ]

    const connect: FooterLink[] = [
        { label: footer.twitter, href: '#', external: true },
        { label: footer.linkedin, href: '#', external: true },
        { label: footer.facebook, href: '#', external: true },
        { label: footer.instagram, href: '#', external: true },
    ]

    const bottomLinks: FooterLink[] = [
        { label: footer.privacy, href: '#', external: false },
        { label: footer.terms, href: '#', external: false },
        { label: footer.cookies, href: '#', external: false },
    ]

    return (
        <footer className="bg-surface-dark text-text-inverse">
            <div className="mx-auto max-w-7xl px-6 py-16">
                <div className="grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1fr]">
                    <div className="flex flex-col gap-4">
                        <Link href="/" className="font-display text-xl font-semibold text-text-inverse">
                            {footer.brand}
                        </Link>
                        <p className="max-w-xs text-sm leading-relaxed text-text-inverse/70">
                            {footer.tagline}
                        </p>
                    </div>

                    <FooterLinkColumn heading={footer.company} links={company} />
                    <FooterLinkColumn heading={footer.legal} links={legal} />
                    <FooterLinkColumn heading={footer.connect} links={connect} />
                </div>

                <div className="mt-12 flex flex-col gap-4 border-t border-white/10 pt-8 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-text-inverse/60">
                        {footer.copyright.replace(':year', String(year))}
                    </p>

                    <div className="flex items-center gap-6">
                        {bottomLinks.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="text-sm text-text-inverse/60 transition-colors hover:text-text-inverse"
                            >
                                {link.label}
                            </Link>
                        ))}
                    </div>
                </div>
            </div>
        </footer>
    )
}
