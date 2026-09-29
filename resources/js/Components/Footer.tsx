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
        <footer className="border-t border-rule bg-paper text-ink">
            <div className="mx-auto flex max-w-360 flex-col gap-7 px-5 pt-10 pb-7 md:px-10 lg:gap-12 lg:px-16 lg:pt-16 lg:pb-8">
                <div className="grid grid-cols-2 gap-x-4 gap-y-6 lg:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))] lg:gap-8">
                    <div className="col-span-2 flex flex-col gap-2.5 lg:col-span-1">
                        <Link href="/" className="self-start text-[15px] font-medium tracking-[0.04em] text-ink lg:text-[17px]">
                            {footer.brand}
                        </Link>
                        <p className="max-w-[26em] text-sm/[1.55] text-ink-muted lg:text-sm/[1.6]">{footer.tagline}</p>
                    </div>

                    <FooterLinkColumn heading={footer.company} links={company} />
                    <FooterLinkColumn heading={footer.legal} links={legal} />
                    <FooterLinkColumn heading={footer.connect} links={connect} />
                </div>

                <div className="flex flex-col gap-4 border-t border-rule pt-4 text-xs text-ink-muted lg:flex-row lg:items-center lg:justify-between lg:border-t-0 lg:pt-0 lg:text-[13px]">
                    <p>{footer.copyright.replace(':year', String(year))}</p>

                    <div className="hidden items-center gap-6 lg:flex">
                        {bottomLinks.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="text-ink-muted transition-colors duration-150 hover:text-forest"
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
