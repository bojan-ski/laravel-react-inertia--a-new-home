import { useState } from 'react'
import { Link, usePage } from '@inertiajs/react'
import Button from '@/Components/UI/Button'
import type { CookieConsent, SharedProps } from '@/types/shared'

const COOKIE_NAME = 'cookie_consent'
const ONE_YEAR_IN_SECONDS = 60 * 60 * 24 * 365

function storeConsent(consent: CookieConsent) {
    const secure = window.location.protocol === 'https:' ? '; Secure' : ''

    document.cookie = `${COOKIE_NAME}=${consent}; Max-Age=${ONE_YEAR_IN_SECONDS}; Path=/; SameSite=Lax${secure}`
}

export default function CookieBanner() {
    const { cookieConsent, cookieBanner } = usePage<SharedProps>().props
    const [chosen, setChosen] = useState(false)

    if (cookieConsent !== null || chosen) {
        return null
    }

    function choose(consent: CookieConsent) {
        storeConsent(consent)
        setChosen(true)
    }

    return (
        <section aria-label={cookieBanner.title} className="fixed inset-x-0 bottom-0 z-50 bg-ink text-paper">
            <div className="mx-auto flex max-w-360 flex-col gap-3.5 px-5 py-6 md:px-10 lg:grid lg:grid-cols-[260px_minmax(0,1fr)_auto] lg:items-center lg:gap-10 lg:px-16 lg:py-7">
                <h2 className="text-[26px] font-light tracking-[-0.015em]">{cookieBanner.title}</h2>

                <p className="text-sm/[1.6] text-paper/85">
                    {cookieBanner.body}{' '}
                    <Link
                        href="/cookie-policy"
                        className="border-b border-current text-forest-200 transition-colors duration-150 hover:text-paper"
                    >
                        {cookieBanner.policy_link}
                    </Link>
                </p>

                <div className="grid grid-cols-2 gap-2.5 lg:flex">
                    <Button variant="on-photo-outline" onClick={() => choose('rejected')}>
                        {cookieBanner.reject}
                    </Button>
                    <Button variant="on-photo" onClick={() => choose('accepted')}>
                        {cookieBanner.accept}
                    </Button>
                </div>
            </div>
        </section>
    )
}
