import type { AccountTab, AccountTabsTranslations } from '@/types/navigation'
import type { UserRole } from '@/types/shared'

export function buildAccountTabs(role: UserRole, translations: AccountTabsTranslations): AccountTab[] {
    if (role === 'admin') {
        return []
    }

    const isAgency = role === 'agency'

    return [
        isAgency
            ? { label: translations.agency_profile, href: '/agency' }
            : { label: translations.my_listings, href: '/profile' },
        { label: translations.favorites, href: '/bookmarks' },
        { label: translations.saved_searches, href: '/saved-searches' },
        { label: translations.messages, href: '/conversations' },
        { label: translations.account_settings, href: isAgency ? '/agency/settings' : '/profile/settings' },
    ]
}

export function isAccountTabActive(tab: AccountTab, url: string): boolean {
    return url.split(/[?#]/)[0] === tab.href
}
