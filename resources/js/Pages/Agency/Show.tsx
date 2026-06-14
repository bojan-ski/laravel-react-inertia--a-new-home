import { Head, Link } from '@inertiajs/react'
import { Pencil } from 'lucide-react'
import AgencyListingsSection from '@/Components/Agency/AgencyListingsSection'
import AgencyProfileSection from '@/Components/Agency/AgencyProfileSection'
import AgencyStatusPanel from '@/Components/Agency/AgencyStatusPanel'
import PublicLayout from '@/Layouts/PublicLayout'
import type { AgencyShowPageProps } from '@/types/agency'

export default function Show({ agency, listings, translations, propertyTypeLabels }: AgencyShowPageProps) {
    return (
        <PublicLayout>
            <Head title={agency.name} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto flex max-w-5xl flex-col gap-6 px-6 py-10 sm:flex-row sm:items-start sm:justify-between md:py-14">
                    <div>
                        <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                            {translations.title}
                        </h1>
                        <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Link
                            href="/agency/edit"
                            className="inline-flex items-center gap-2 rounded-full border border-border-strong px-5 py-2.5 text-sm font-medium text-text-soft transition-colors hover:border-text hover:text-text"
                        >
                            <Pencil className="size-4" />
                            {translations.edit}
                        </Link>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-5xl px-6 py-8">
                <AgencyStatusPanel agency={agency} translations={translations} />

                <div className="mt-8">
                    <AgencyProfileSection agency={agency} translations={translations.profile} />
                </div>

                <AgencyListingsSection
                    listings={listings}
                    translations={translations.listings}
                    propertyTypeLabels={propertyTypeLabels}
                />
            </div>
        </PublicLayout>
    )
}
