import { Head, Link, usePage } from '@inertiajs/react'
import { Clock, Pencil, Settings } from 'lucide-react'
import AgencyListingsSection from '@/Components/Agency/AgencyListingsSection'
import AgencyProfileSection from '@/Components/Agency/AgencyProfileSection'
import AgencyStatusPanel from '@/Components/Agency/AgencyStatusPanel'
import FlashBanner from '@/Components/UI/FlashBanner'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { AgencyShowPageProps } from '@/types/agency'
import type { SharedProps } from '@/types/shared'

export default function Show({ agency, pendingChange, listings, translations, propertyTypeLabels }: AgencyShowPageProps) {
    const { flash } = usePage<SharedProps>().props
    const submittedOn = pendingChange ? new Date(pendingChange.submittedAt).toLocaleDateString() : null

    return (
        <ListingsLayout>
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
                            href="/agency/settings"
                            className="inline-flex items-center gap-2 rounded-full border border-border-strong px-5 py-2.5 text-sm font-medium text-text-soft transition-colors hover:border-text hover:text-text"
                        >
                            <Settings className="size-4" />
                            {translations.settings}
                        </Link>
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
                {flash.success && <FlashBanner message={flash.success} variant="success" />}
                {flash.error && <FlashBanner message={flash.error} variant="error" />}

                {submittedOn && (
                    <div className="mb-6 flex items-start gap-3 rounded-lg border border-accent bg-accent-soft px-5 py-4 text-text">
                        <Clock className="mt-0.5 size-5 shrink-0 text-accent" />
                        <p className="text-sm">{translations.pending_changes.replace(':date', submittedOn)}</p>
                    </div>
                )}

                <AgencyStatusPanel agency={agency} translations={translations} />

                <div className="mt-8">
                    <AgencyProfileSection agency={agency} translations={translations.profile} />
                </div>

                <AgencyListingsSection
                    listings={listings}
                    translations={translations.listings}
                    propertyTypeLabels={propertyTypeLabels}
                    agencyStatus={agency.status}
                />
            </div>
        </ListingsLayout>
    )
}
