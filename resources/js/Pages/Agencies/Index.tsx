import { Head } from '@inertiajs/react'
import AgenciesEmptyState from '@/Components/Agencies/AgenciesEmptyState'
import AgencyCard from '@/Components/Agencies/AgencyCard'
import AgencyDirectorySearchBar from '@/Components/Agencies/AgencyDirectorySearchBar'
import { countLabel } from '@/Components/Listings/listingFormat'
import Pagination from '@/Components/UI/Pagination'
import useReveal from '@/Components/UI/useReveal'
import PublicLayout from '@/Layouts/PublicLayout'
import type { AgenciesIndexPageProps } from '@/types/agency'

export default function Index({ agencies, filters, translations }: AgenciesIndexPageProps) {
    const hasActiveSearch = Boolean(filters.search)
    const reveal = useReveal<HTMLUListElement>('left', agencies.data)

    return (
        <PublicLayout>
            <Head title={translations.title} />

            <div className="mx-auto max-w-360 px-5 pb-12 md:px-10 lg:pb-18 xl:px-16">
                <header className="flex flex-col gap-4 pt-7 pb-5 lg:gap-10 lg:pt-18 lg:pb-10">
                    <div className="flex flex-col gap-4 lg:grid lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-end lg:gap-16">
                        <h1 className="text-[52px] leading-[0.92] font-extralight tracking-[-0.045em] md:text-[80px] lg:text-[112px] lg:leading-[0.9]">
                            {translations.heading}
                        </h1>
                        <p className="text-[15px]/normal font-book text-ink/70 lg:pb-2 lg:text-lg/[1.55]">
                            {translations.subtitle}
                        </p>
                    </div>

                    <AgencyDirectorySearchBar
                        filters={filters}
                        translations={translations.search}
                        countLabel={countLabel(agencies.total, translations.count_one, translations.count_other)}
                    />
                </header>

                {agencies.data.length === 0 ? (
                    <AgenciesEmptyState translations={translations.empty} hasActiveSearch={hasActiveSearch} />
                ) : (
                    <>
                        <ul {...reveal}>
                            {agencies.data.map((agency) => (
                                <AgencyCard key={agency.id} agency={agency} translations={translations} />
                            ))}
                        </ul>

                        <Pagination paginator={agencies} labels={translations.pagination} className="mt-4 lg:mt-12" />
                    </>
                )}
            </div>
        </PublicLayout>
    )
}
