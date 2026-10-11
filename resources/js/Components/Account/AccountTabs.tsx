import { Link, usePage } from '@inertiajs/react'
import { buildAccountTabs, isAccountTabActive } from '@/Components/Account/accountTabItems'
import type { SharedProps } from '@/types/shared'

export default function AccountTabs() {
    const { props, url } = usePage<SharedProps>()
    const { auth, accountTabs } = props

    if (!auth.user || !accountTabs) {
        return null
    }

    const tabs = buildAccountTabs(auth.user.role, accountTabs)

    if (tabs.length === 0) {
        return null
    }

    return (
        <nav aria-label={accountTabs.label}>
            <ul className="-mx-5 flex gap-5.5 overflow-x-auto overflow-y-hidden bg-linear-to-r from-rule to-rule bg-size-[100%_1px] bg-bottom bg-no-repeat px-5 text-sm whitespace-nowrap md:mx-0 md:gap-8 md:px-0 md:text-[15px]">
                {tabs.map((tab) => {
                    const active = isAccountTabActive(tab, url)

                    return (
                        <li key={tab.href} className="shrink-0">
                            <Link
                                href={tab.href}
                                aria-current={active ? 'page' : undefined}
                                className={`block border-b pt-3.5 pb-3.25 transition-colors duration-150 focus-visible:-outline-offset-2 ${
                                    active ? 'border-forest text-forest' : 'border-transparent text-ink-muted hover:text-forest'
                                }`}
                            >
                                {tab.label}
                            </Link>
                        </li>
                    )
                })}
            </ul>
        </nav>
    )
}
