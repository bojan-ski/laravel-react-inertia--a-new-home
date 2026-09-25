import { Head } from '@inertiajs/react'
import AgenciesEmptyState from '@/Components/Agencies/AgenciesEmptyState'
import AgencyCard from '@/Components/Agencies/AgencyCard'
import AgencyDirectorySearchBar from '@/Components/Agencies/AgencyDirectorySearchBar'
import Pagination from '@/Components/UI/Pagination'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { AgenciesIndexPageProps } from '@/types/agency'

export default function Index({ agencies, filters, translations, listingsNav }: AgenciesIndexPageProps) {
    const hasActiveSearch = Boolean(filters.search)

    return (
        <ListingsLayout nav={listingsNav}>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto max-w-7xl px-6 py-10 md:py-14">
                    <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                        {translations.heading}
                    </h1>
                    <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                </div>
            </div>

            <AgencyDirectorySearchBar filters={filters} translations={translations.search} />

            <div className="mx-auto max-w-7xl px-6 py-8 pb-16">
                <p className="text-sm text-text-muted">
                    {translations.count_prefix} <strong className="text-text">{agencies.total}</strong>{' '}
                    {translations.count_suffix}
                </p>

                {agencies.data.length === 0 ? (
                    <AgenciesEmptyState translations={translations.empty} hasActiveSearch={hasActiveSearch} />
                ) : (
                    <>
                        <div className="mt-6 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                            {agencies.data.map((agency) => (
                                <AgencyCard key={agency.id} agency={agency} />
                            ))}
                        </div>

                        <Pagination paginator={agencies} labels={translations.pagination} />
                    </>
                )}
            </div>
        </ListingsLayout>
    )
}
