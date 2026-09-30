import AgencyListingRow from '@/Components/Agency/AgencyListingRow'
import { countLabel } from '@/Components/Listings/listingFormat'
import Button from '@/Components/UI/Button'
import EmptyState from '@/Components/UI/EmptyState'
import type { AgencyListingsSectionProps } from '@/types/agency'

export default function AgencyListingsSection({ listings, translations, agencyStatus }: AgencyListingsSectionProps) {
    const isApproved = agencyStatus === 'approved'
    const plus = <span aria-hidden="true">+</span>
    const newListingClasses = 'min-h-11 shrink-0 gap-4! lg:min-h-12 lg:gap-8!'

    return (
        <section aria-labelledby="agency-listings-heading">
            <div className="flex items-center justify-between gap-4 lg:items-end">
                <div className="flex min-w-0 flex-col gap-0.5 lg:gap-1.5">
                    <h2
                        id="agency-listings-heading"
                        className="text-[30px] leading-tight font-airy tracking-[-0.02em] lg:text-[40px] lg:tracking-[-0.03em]"
                    >
                        {translations.heading}
                    </h2>
                    <p className="text-[13px] text-ink-muted lg:text-sm">
                        {countLabel(listings.length, translations.count_one, translations.count)}
                    </p>
                </div>

                {isApproved ? (
                    <Button href="/agency/listings/create" trailing={plus} className={newListingClasses}>
                        {translations.new_listing}
                    </Button>
                ) : (
                    <Button
                        disabled
                        trailing={plus}
                        aria-describedby="agency-new-listing-help"
                        className={newListingClasses}
                    >
                        {translations.new_listing}
                    </Button>
                )}
            </div>

            {!isApproved && (
                <p id="agency-new-listing-help" className="mt-2 text-sm text-ink-muted">
                    {translations.new_listing_disabled_help}
                </p>
            )}

            {listings.length === 0 ? (
                <EmptyState
                    className="mt-4"
                    title={isApproved ? translations.empty_headline_approved : translations.empty_headline}
                    hint={isApproved ? translations.empty_subline_approved : translations.empty_subline}
                    action={
                        isApproved && (
                            <Button href="/agency/listings/create" variant="secondary" size="sm" className="mt-2">
                                {translations.create_listing}
                            </Button>
                        )
                    }
                />
            ) : (
                <ul className="mt-3 border-t border-rule lg:mt-4">
                    {listings.map((listing) => (
                        <AgencyListingRow key={listing.id} listing={listing} translations={translations} />
                    ))}
                </ul>
            )}
        </section>
    )
}
