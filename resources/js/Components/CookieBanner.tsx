import { useState } from 'react'
import { Link, usePage } from '@inertiajs/react'
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
        <section
            aria-label={cookieBanner.title}
            className="fixed inset-x-4 bottom-4 z-50 rounded-2xl border border-border bg-surface p-5 shadow-lg sm:inset-x-auto sm:left-6 sm:bottom-6 sm:max-w-sm"
        >
            <h2 className="font-semibold text-text">{cookieBanner.title}</h2>
            <p className="mt-2 text-sm leading-relaxed text-text-soft">{cookieBanner.body}</p>
            <Link
                href="/cookie-policy"
                className="mt-2 inline-block text-sm font-medium text-brand transition-colors hover:text-brand-dark"
            >
                {cookieBanner.policy_link}
            </Link>

            <div className="mt-4 grid grid-cols-2 gap-3">
                <button
                    type="button"
                    onClick={() => choose('rejected')}
                    className="rounded-full border border-border-strong px-4 py-2.5 text-sm font-semibold text-text transition-colors hover:border-text"
                >
                    {cookieBanner.reject}
                </button>
                <button
                    type="button"
                    onClick={() => choose('accepted')}
                    className="rounded-full bg-brand px-4 py-2.5 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                >
                    {cookieBanner.accept}
                </button>
            </div>
        </section>
    )
}
