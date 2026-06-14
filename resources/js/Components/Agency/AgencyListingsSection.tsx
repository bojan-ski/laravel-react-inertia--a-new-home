import { Link } from '@inertiajs/react'
import { Plus } from 'lucide-react'
import AgencyListingRow from '@/Components/Agency/AgencyListingRow'
import type { AgencyListingsSectionProps } from '@/types/agency'

export default function AgencyListingsSection({
    listings,
    translations,
    propertyTypeLabels,
    agencyStatus,
}: AgencyListingsSectionProps) {
    const isApproved = agencyStatus === 'approved'
    const countTemplate = listings.length === 1 ? translations.count_one : translations.count
    const countLabel = countTemplate.replace(':count', listings.length.toString())

    return (
        <section className="mt-10">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-3">
                    <h2 className="font-display text-xl font-semibold text-text">{translations.heading}</h2>
                    <span className="text-sm text-text-muted">{countLabel}</span>
                </div>

                <div className="flex flex-col items-start gap-1.5 sm:items-end">
                    {isApproved ? (
                        <Link
                            href="/agency/listings/create"
                            className="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                        >
                            <Plus className="size-4" />
                            {translations.new_listing}
                        </Link>
                    ) : (
                        <>
                            <span
                                aria-disabled="true"
                                className="inline-flex cursor-not-allowed items-center gap-2 rounded-full bg-surface-muted px-5 py-2.5 text-sm font-semibold text-text-muted"
                            >
                                <Plus className="size-4" />
                                {translations.new_listing}
                            </span>
                            <p className="text-xs text-text-muted">{translations.new_listing_disabled_help}</p>
                        </>
                    )}
                </div>
            </div>

            {listings.length === 0 ? (
                <div className="mt-6 flex flex-col items-center justify-center gap-2 rounded-xl border border-border bg-surface py-16 text-center">
                    <p className="text-lg font-semibold text-text">
                        {isApproved ? translations.empty_headline_approved : translations.empty_headline}
                    </p>
                    <p className="max-w-md text-text-soft">
                        {isApproved ? translations.empty_subline_approved : translations.empty_subline}
                    </p>
                    {isApproved && (
                        <Link
                            href="/agency/listings/create"
                            className="mt-4 inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                        >
                            <Plus className="size-4" />
                            {translations.create_listing}
                        </Link>
                    )}
                </div>
            ) : (
                <div className="mt-6 flex flex-col gap-4">
                    {listings.map((listing) => (
                        <AgencyListingRow
                            key={listing.id}
                            listing={listing}
                            translations={translations}
                            propertyTypeLabels={propertyTypeLabels}
                        />
                    ))}
                </div>
            )}
        </section>
    )
}
