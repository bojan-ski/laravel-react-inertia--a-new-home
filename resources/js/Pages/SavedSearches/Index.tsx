import { Head, usePage } from '@inertiajs/react'
import AccountPageHeader from '@/Components/Account/AccountPageHeader'
import SavedSearchCard from '@/Components/SavedSearches/SavedSearchCard'
import Button from '@/Components/UI/Button'
import EmptyState from '@/Components/UI/EmptyState'
import FlashBanner from '@/Components/UI/FlashBanner'
import PublicLayout from '@/Layouts/PublicLayout'
import type { SavedSearchesIndexPageProps } from '@/types/saved-search'
import type { SharedProps } from '@/types/shared'

export default function Index({ searches, maxSearches, translations }: SavedSearchesIndexPageProps) {
    const { flash } = usePage<SharedProps>().props
    const count = translations.count
        .replace(':count', String(searches.length))
        .replace(':max', String(maxSearches))

    return (
        <PublicLayout>
            <Head title={translations.title} />

            <AccountPageHeader
                title={translations.title}
                subtitle={translations.subtitle}
                action={<p className="text-sm text-ink-muted">{count}</p>}
                mobileAction={<p className="mt-1.5 text-[13px] text-ink-muted">{count}</p>}
            />

            <div className="px-5 pt-2 pb-20 md:px-10 xl:px-16">
                {(flash.success || flash.error) && (
                    <div className="pt-6">
                        {flash.success && <FlashBanner message={flash.success} variant="success" />}
                        {flash.error && <FlashBanner message={flash.error} variant="error" />}
                    </div>
                )}

                {searches.length === 0 ? (
                    <EmptyState
                        className="mt-8"
                        title={translations.empty.headline}
                        hint={translations.empty.hint}
                        action={
                            <Button href="/listings" variant="secondary" size="sm" className="mt-2">
                                {translations.empty.cta}
                            </Button>
                        }
                    />
                ) : (
                    <ul>
                        {searches.map((search) => (
                            <SavedSearchCard key={search.id} search={search} translations={translations} />
                        ))}
                    </ul>
                )}
            </div>
        </PublicLayout>
    )
}
