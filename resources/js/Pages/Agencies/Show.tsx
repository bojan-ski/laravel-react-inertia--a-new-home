import { Head, Link } from '@inertiajs/react'
import { BadgeCheck, Building2, ChevronLeft, MapPin } from 'lucide-react'
import AgencyContactList from '@/Components/Agencies/AgencyContactList'
import { agencyRegistrationLines } from '@/Components/Agencies/agencyFormat'
import ListingCard from '@/Components/Listings/ListingCard'
import Pagination from '@/Components/UI/Pagination'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { AgencyPublicPageProps } from '@/types/agency'

export default function Show({ agency, listings, translations, listingTranslations }: AgencyPublicPageProps) {
    const show = translations.show
    const countNoun = listings.total === 1 ? show.listings_count_one : show.listings_count_other
    const registration = agencyRegistrationLines(agency, translations)
    const memberSince = agency.approvedAt
        ? new Date(agency.approvedAt).toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
        : null

    return (
        <ListingsLayout>
            <Head title={agency.name} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto max-w-7xl px-6 py-8 md:py-12">
                    <Link
                        href="/agencies"
                        className="inline-flex items-center gap-1 text-sm text-text-muted transition-colors hover:text-text"
                    >
                        <ChevronLeft className="size-4" />
                        {show.back}
                    </Link>

                    <div className="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_320px]">
                        <div>
                            <p className="text-xs font-medium uppercase tracking-wider text-accent">{show.eyebrow}</p>
                            <h1 className="mt-2 font-display text-3xl font-semibold text-text sm:text-4xl">{agency.name}</h1>

                            <div className="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-text-muted">
                                {agency.address && (
                                    <span className="flex items-center gap-1.5">
                                        <MapPin className="size-4 shrink-0" />
                                        {agency.address}
                                    </span>
                                )}
                                {registration.map((line, index) => (
                                    <span key={line} className="flex items-center gap-1.5">
                                        {index === 0 && <BadgeCheck className="size-4 shrink-0" />}
                                        {line}
                                    </span>
                                ))}
                                {memberSince && <span>{show.member_since.replace(':date', memberSince)}</span>}
                            </div>

                            {agency.description && (
                                <p className="mt-6 max-w-3xl whitespace-pre-line leading-relaxed text-text-soft">
                                    {agency.description}
                                </p>
                            )}
                        </div>

                        <aside className="self-start rounded-2xl border border-border bg-background p-6">
                            <h2 className="text-xs font-medium uppercase tracking-wide text-text-muted">
                                {show.contact_heading}
                            </h2>
                            <div className="mt-4">
                                <AgencyContactList agency={agency} />
                            </div>
                        </aside>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-7xl px-6 py-10 pb-16">
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 className="font-display text-2xl font-semibold text-text">
                        {show.listings_heading.replace(':name', agency.name)}
                    </h2>
                    <p className="text-sm text-text-muted">
                        <strong className="text-text">{listings.total}</strong> {countNoun}
                    </p>
                </div>

                {listings.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-3 py-20 text-center">
                        <Building2 className="size-10 text-text-muted" />
                        <p className="text-text-soft">{show.empty}</p>
                    </div>
                ) : (
                    <>
                        <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {listings.data.map((listing) => (
                                <ListingCard
                                    key={listing.id}
                                    listing={listing}
                                    translations={listingTranslations.card}
                                    propertyTypeLabels={listingTranslations.filters.property_types}
                                />
                            ))}
                        </div>

                        <Pagination paginator={listings} labels={translations.pagination} />
                    </>
                )}
            </div>
        </ListingsLayout>
    )
}
