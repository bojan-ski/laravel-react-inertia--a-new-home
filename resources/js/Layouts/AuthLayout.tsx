import { Link, usePage } from '@inertiajs/react'
import NavbarBackBar from '@/Components/NavbarBackBar'
import type { AuthImage } from '@/types/auth'
import type { AuthLayoutProps } from '@/types/layout'
import type { SharedProps } from '@/types/shared'

const images: Record<AuthImage, string> = {
    'sign-in': '/images/family-house-white-brown-portrait.webp',
    register: '/images/stone-house-hillside.webp',
    password: '/images/whitewashed-house-timber-doors.webp',
}

export default function AuthLayout({ children, translations, image }: AuthLayoutProps) {
    const { nav, footer } = usePage<SharedProps>().props
    const year = new Date().getFullYear()

    return (
        <div className="min-h-dvh bg-paper text-ink lg:grid lg:grid-cols-2">
            <div className="relative hidden lg:block">
                <div className="sticky top-0 h-dvh">
                    <picture>
                        <source media="(min-width: 64rem)" srcSet={images[image]} />
                        <img alt="" className="absolute inset-0 size-full object-cover" />
                    </picture>
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0 bg-[linear-gradient(to_bottom,rgba(12,20,15,0.35),rgba(12,20,15,0)_22%)]"
                    />
                    <Link
                        href="/"
                        className="absolute top-10 left-12 text-[17px] font-medium tracking-[0.04em] text-paper transition-colors duration-150 hover:text-forest-200 focus-visible:outline-paper"
                    >
                        {nav.brand}
                    </Link>
                </div>
            </div>

            <div className="flex flex-col lg:min-h-dvh lg:px-12 lg:pt-10 lg:pb-12 xl:pr-16 xl:pl-24">
                <NavbarBackBar href="/" label={translations.back_home} className="lg:hidden" />

                <div className="hidden justify-end lg:flex">
                    <Link href="/" className="text-sm text-ink-muted transition-colors duration-150 hover:text-forest">
                        <span aria-hidden="true">←&nbsp;</span>
                        {translations.back_home}
                    </Link>
                </div>

                <main className="w-full px-5 pt-10 pb-12 md:mx-auto md:max-w-115 md:px-0 lg:mx-0 lg:my-auto lg:py-12">
                    {children}
                </main>

                <p className="hidden text-xs text-ink-muted lg:block">
                    {translations.copyright.replace(':year', String(year))}
                    {' · '}
                    <Link href="/privacy-policy" className="transition-colors duration-150 hover:text-forest">
                        {footer.privacy}
                    </Link>
                    {' · '}
                    <Link href="/terms" className="transition-colors duration-150 hover:text-forest">
                        {footer.terms}
                    </Link>
                </p>
            </div>
        </div>
    )
}
