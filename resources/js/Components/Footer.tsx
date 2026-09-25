import { Link, usePage } from '@inertiajs/react'
import FooterLinkColumn from '@/Components/FooterLinkColumn'
import type { FooterLink } from '@/types/navigation'
import type { SharedProps } from '@/types/shared'

export default function Footer() {
    const { footer } = usePage<SharedProps>().props
    const year = new Date().getFullYear()

    const company: FooterLink[] = [
        { label: footer.about, href: '/about', external: false },
        { label: footer.contact, href: '/contact', external: false },
    ]

    const legal: FooterLink[] = [
        { label: footer.privacy_policy, href: '/privacy-policy', external: false },
        { label: footer.terms_of_service, href: '/terms', external: false },
        { label: footer.cookie_policy, href: '/cookie-policy', external: false },
        { label: footer.gdpr, href: 'https://eur-lex.europa.eu/eli/reg/2016/679/oj', external: true },
    ]

    const connect: FooterLink[] = [
        { label: footer.twitter, href: 'https://x.com', external: true },
        { label: footer.linkedin, href: 'https://www.linkedin.com', external: true },
        { label: footer.facebook, href: 'https://www.facebook.com', external: true },
        { label: footer.instagram, href: 'https://www.instagram.com', external: true },
    ]

    const bottomLinks: FooterLink[] = [
        { label: footer.privacy, href: '/privacy-policy', external: false },
        { label: footer.terms, href: '/terms', external: false },
        { label: footer.cookies, href: '/cookie-policy', external: false },
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
