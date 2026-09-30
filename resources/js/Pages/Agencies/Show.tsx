import { Head, Link } from '@inertiajs/react'
import { BadgeCheck } from 'lucide-react'
import AgencyContactList from '@/Components/Agencies/AgencyContactList'
import AgencyListingCard from '@/Components/Agencies/AgencyListingCard'
import { agencyRegistrationLines, descriptionParagraphs, formatMonthYear } from '@/Components/Agencies/agencyFormat'
import EmptyState from '@/Components/UI/EmptyState'
import Kicker from '@/Components/UI/Kicker'
import LogoTile from '@/Components/UI/LogoTile'
import Pagination from '@/Components/UI/Pagination'
import PublicLayout from '@/Layouts/PublicLayout'
import type { AgencyPublicPageProps } from '@/types/agency'

const gutterClass = 'mx-auto max-w-360 px-5 md:px-10 xl:px-16'
const bleedClass = '-mx-5 px-5 md:-mx-10 md:px-10 lg:mx-0 lg:px-0'

export default function Show({ agency, listings, translations, listingTranslations }: AgencyPublicPageProps) {
    const show = translations.show
    const countNoun = listings.total === 1 ? show.listings_count_one : show.listings_count_other
    const registration = agencyRegistrationLines(agency, translations)
    const [lead, ...paragraphs] = descriptionParagraphs(agency.description)
    const hasAbout = lead !== undefined || registration.length > 0

    return (
        <PublicLayout back={{ href: '/agencies', label: show.back }}>
            <Head title={agency.name} />

            <header className={`${gutterClass} flex flex-col pt-7 pb-7 lg:gap-12 lg:pt-8 lg:pb-16`}>
                <Link
                    href="/agencies"
                    className="hidden self-start text-sm text-ink-muted transition-colors duration-150 hover:text-forest lg:inline-flex"
                >
                    <span aria-hidden="true">←&nbsp;</span>
                    {show.back}
                </Link>

                <div className="flex flex-col gap-4 lg:grid lg:grid-cols-[160px_minmax(0,1fr)_auto] lg:items-end lg:gap-12">
                    <LogoTile name={agency.name} size="hero" />

                    <div className="flex min-w-0 flex-col gap-4 lg:gap-3.5">
                        <Kicker as="p" size="sm" className="mt-2 flex items-center gap-2 lg:mt-0 lg:text-xs">
                            <BadgeCheck className="hidden size-4 lg:block" strokeWidth={1.25} aria-hidden="true" />
                            {show.eyebrow}
                        </Kicker>
                        <h1 className="text-5xl leading-[0.95] font-extralight tracking-[-0.045em] wrap-break-word md:text-7xl lg:text-8xl lg:leading-[0.92]">
                            {agency.name}
                        </h1>
                    </div>

                    {agency.approvedAt && (
                        <p className="text-[13px] text-ink-muted lg:pb-2.5 lg:text-sm">
                            {show.member_since.replace(':date', formatMonthYear(agency.approvedAt))}
                        </p>
                    )}
                </div>
            </header>

            <section className="lg:border-t lg:border-rule">
                <div
                    className={`${gutterClass} lg:grid lg:grid-cols-[minmax(0,7fr)_minmax(0,1fr)_minmax(0,4fr)] lg:pt-14 lg:pb-24`}
                >
                    {hasAbout && (
                        <div className={`${bleedClass} flex flex-col gap-3.5 border-t border-rule py-6 lg:gap-7 lg:border-t-0 lg:py-0`}>
                            {lead !== undefined && (
                                <p className="text-[21px]/[1.4] font-airy whitespace-pre-line lg:text-[32px]/[1.3] lg:tracking-[-0.015em]">
                                    {lead}
                                </p>
                            )}

                            {paragraphs.map((paragraph, index) => (
                                <p key={index} className="text-base/[1.75] font-book whitespace-pre-line text-ink/78">
                                    {paragraph}
                                </p>
                            ))}

                            {registration.length > 0 && (
                                <div className="flex flex-col gap-1 text-xs text-ink/62 lg:flex-row lg:flex-wrap lg:gap-x-7 lg:gap-y-2 lg:border-t lg:border-rule lg:pt-5 lg:text-[13px]">
                                    {registration.map((line) => (
                                        <span key={line}>{line}</span>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    <aside
                        className={`${bleedClass} border-t border-rule py-6 lg:col-start-3 lg:self-start lg:border-ink lg:pt-6 lg:pb-0`}
                    >
                        <AgencyContactList agency={agency} heading={show.contact_heading} />
                    </aside>
                </div>
            </section>

            <section className="border-t border-rule lg:border-t-0">
                <div className={`${gutterClass} pt-8 pb-10 lg:pt-0 lg:pb-30`}>
                    <div className="flex flex-col gap-1 lg:flex-row lg:items-baseline lg:justify-between lg:gap-8 lg:border-t lg:border-rule lg:pt-16">
                        <h2 className="text-[30px] leading-tight font-extralight tracking-[-0.03em] wrap-break-word lg:text-[56px] lg:tracking-[-0.035em]">
                            {show.listings_heading.replace(':name', agency.name)}
                        </h2>
                        <p className="shrink-0 text-[13px] text-ink-muted lg:text-sm">
                            {listings.total} {countNoun}
                        </p>
                    </div>

                    {listings.data.length === 0 ? (
                        <EmptyState title={show.empty} className="mt-6 lg:mt-10" />
                    ) : (
                        <>
                            <div className="mt-1 grid grid-cols-1 md:mt-8 md:grid-cols-2 md:gap-x-8 md:gap-y-12 lg:mt-10 lg:grid-cols-3">
                                {listings.data.map((listing) => (
                                    <AgencyListingCard
                                        key={listing.id}
                                        listing={listing}
                                        translations={listingTranslations.card}
                                        propertyTypeLabels={listingTranslations.filters.property_types}
                                    />
                                ))}
                            </div>

                            <Pagination paginator={listings} labels={translations.pagination} className="mt-10 lg:mt-12" />
                        </>
                    )}
                </div>
            </section>
        </PublicLayout>
    )
}
