import { Head, Link, usePage } from '@inertiajs/react'
import { ChevronLeft, Search } from 'lucide-react'
import SavedSearchCard from '@/Components/SavedSearches/SavedSearchCard'
import FlashBanner from '@/Components/UI/FlashBanner'
import PublicLayout from '@/Layouts/PublicLayout'
import type { SavedSearchesIndexPageProps } from '@/types/saved-search'
import type { SharedProps } from '@/types/shared'

export default function Index({ searches, maxSearches, translations }: SavedSearchesIndexPageProps) {
    const { flash } = usePage<SharedProps>().props

    return (
        <PublicLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto max-w-4xl px-6 py-10">
                    <Link
                        href="/bookmarks"
                        className="inline-flex items-center gap-1 text-sm text-text-muted transition-colors hover:text-text"
                    >
                        <ChevronLeft className="size-4" />
                        {translations.back}
                    </Link>
                    <h1 className="mt-4 font-display text-3xl font-semibold text-text">{translations.title}</h1>
                    <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                </div>
            </div>

            <div className="mx-auto max-w-4xl px-6 py-8">
                {flash.success && <FlashBanner message={flash.success} variant="success" />}
                {flash.error && <FlashBanner message={flash.error} variant="error" />}

                {searches.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 py-24 text-center">
                        <Search className="size-10 text-text-muted" />
                        <p className="text-lg text-text-soft">{translations.empty.headline}</p>
                        <p className="max-w-md text-sm text-text-muted">{translations.empty.hint}</p>
                        <Link
                            href="/listings"
                            className="rounded-full bg-brand px-6 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                        >
                            {translations.empty.cta}
                        </Link>
                    </div>
                ) : (
                    <>
                        <p className="text-sm text-text-muted">
                            {translations.count
                                .replace(':count', String(searches.length))
                                .replace(':max', String(maxSearches))}
                        </p>

                        <div className="mt-6 flex flex-col gap-4">
                            {searches.map((search) => (
                                <SavedSearchCard key={search.id} search={search} translations={translations} />
                            ))}
                        </div>
                    </>
                )}
            </div>
        </PublicLayout>
    )
}
