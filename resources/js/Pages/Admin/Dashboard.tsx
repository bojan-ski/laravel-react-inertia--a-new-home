import { Head } from '@inertiajs/react'
import StatCard from '@/Components/Admin/StatCard'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminDashboardPageProps, AdminStatSectionProps, StatCardProps } from '@/types/admin'

const wideGridColumns: Record<number, string> = {
    5: 'lg:grid-cols-3 xl:grid-cols-5',
}

function StatSection({ heading, cards }: AdminStatSectionProps) {
    const columns = wideGridColumns[cards.length] ?? 'lg:grid-cols-4'

    return (
        <section>
            <h2 className="border-b border-border pb-3 font-display text-xl font-semibold text-text">
                {heading}
            </h2>
            <div className={`mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 ${columns}`}>
                {cards.map((card) => (
                    <StatCard key={card.label} {...card} />
                ))}
            </div>
        </section>
    )
}

export default function Dashboard({ stats, translations }: AdminDashboardPageProps) {
    const listingCards: StatCardProps[] = [
        { ...translations.stats.total_listings, value: stats.totalListings },
        { ...translations.stats.pending_listings, value: stats.pendingListings },
        { ...translations.stats.approved_listings, value: stats.approvedListings },
        { ...translations.stats.rejected_listings, value: stats.rejectedListings },
        { ...translations.stats.closed_listings, value: stats.closedListings },
    ]

    const userCards: StatCardProps[] = [
        { ...translations.stats.regular_users, value: stats.totalRegularUsers },
        { ...translations.stats.agency_users, value: stats.totalAgencyUsers },
        { ...translations.stats.admins, value: stats.totalAdmins },
    ]

    const moderationCards: StatCardProps[] = [
        { ...translations.stats.pending_agencies, value: stats.pendingAgencies },
        { ...translations.stats.agency_changes, value: stats.pendingAgencyChanges },
        { ...translations.stats.reported_listings, value: stats.reportedListings },
        { ...translations.stats.reported_messages, value: stats.reportedMessages },
        { ...translations.stats.contact_messages, value: stats.newContactMessages },
    ]

    return (
        <AdminLayout>
            <Head title={translations.title} />

            <header>
                <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                    {translations.title}
                </h1>
                <p className="mt-2 text-text-soft">{translations.subtitle}</p>
            </header>

            <div className="mt-10 flex flex-col gap-12">
                <StatSection heading={translations.sections.listings} cards={listingCards} />
                <StatSection heading={translations.sections.users} cards={userCards} />
                <StatSection heading={translations.sections.moderation} cards={moderationCards} />
            </div>
        </AdminLayout>
    )
}
