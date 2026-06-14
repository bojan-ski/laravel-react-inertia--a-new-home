import AgencyListingRow from '@/Components/Agency/AgencyListingRow'
import type { AgencyListingsSectionProps } from '@/types/agency'

export default function AgencyListingsSection({
    listings,
    translations,
    propertyTypeLabels,
}: AgencyListingsSectionProps) {
    const countTemplate = listings.length === 1 ? translations.count_one : translations.count
    const countLabel = countTemplate.replace(':count', listings.length.toString())

    return (
        <section className="mt-10">
            <div className="flex items-center justify-between">
                <h2 className="font-display text-xl font-semibold text-text">{translations.heading}</h2>
                <span className="text-sm text-text-muted">{countLabel}</span>
            </div>

            {listings.length === 0 ? (
                <div className="mt-6 flex flex-col items-center justify-center gap-2 rounded-xl border border-border bg-surface py-16 text-center">
                    <p className="text-lg font-semibold text-text">{translations.empty_headline}</p>
                    <p className="max-w-md text-text-soft">{translations.empty_subline}</p>
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
