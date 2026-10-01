import { Head, usePage } from '@inertiajs/react'
import AdminMobileStats from '@/Components/Admin/AdminMobileStats'
import AdminPageHeader from '@/Components/Admin/AdminPageHeader'
import StatCard from '@/Components/Admin/StatCard'
import FlashBanner from '@/Components/UI/FlashBanner'
import Kicker from '@/Components/UI/Kicker'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminDashboardPageProps, AdminStatSectionProps, StatCardProps } from '@/types/admin'
import type { SharedProps } from '@/types/shared'

function StatSection({ heading, cards }: AdminStatSectionProps) {
    return (
        <section className="flex flex-col gap-3">
            <Kicker as="h2">{heading}</Kicker>
            <div className="grid grid-cols-3 gap-x-8 xl:grid-cols-5">
                {cards.map((card) => (
                    <StatCard key={card.label} {...card} />
                ))}
            </div>
        </section>
    )
}

export default function Dashboard({ stats, translations }: AdminDashboardPageProps) {
    const { flash } = usePage<SharedProps>().props
    const labels = translations.stats

    const pendingListings: StatCardProps = {
        ...labels.pending_listings,
        value: stats.pendingListings,
        attention: true,
        href: '/admin/listings',
    }

    const listingCards: StatCardProps[] = [
        { ...labels.total_listings, value: stats.totalListings },
        pendingListings,
        { ...labels.approved_listings, value: stats.approvedListings },
        { ...labels.rejected_listings, value: stats.rejectedListings },
        { ...labels.closed_listings, value: stats.closedListings },
    ]

    const userCards: StatCardProps[] = [
        { ...labels.regular_users, value: stats.totalRegularUsers, href: '/admin/users?role=regular_user' },
        { ...labels.agency_users, value: stats.totalAgencyUsers, href: '/admin/users?role=agency' },
        { ...labels.admins, value: stats.totalAdmins },
    ]

    const moderationCards: StatCardProps[] = [
        { ...labels.pending_agencies, value: stats.pendingAgencies, attention: true, href: '/admin/agencies' },
        {
            ...labels.agency_changes,
            value: stats.pendingAgencyChanges,
            attention: true,
            href: '/admin/agencies#profile-changes',
        },
        {
            ...labels.reported_listings,
            value: stats.reportedListings,
            attention: true,
            href: '/admin/listing-reports',
        },
        {
            ...labels.reported_messages,
            value: stats.reportedMessages,
            attention: true,
            href: '/admin/reported-messages',
        },
        {
            ...labels.contact_messages,
            value: stats.newContactMessages,
            attention: true,
            href: '/admin/contact-messages',
        },
    ]

    const mobileOtherCards = [...listingCards.filter((card) => card !== pendingListings), ...userCards]

    return (
        <AdminLayout>
            <Head title={translations.title} />

            <AdminPageHeader title={translations.title} subtitle={translations.subtitle} />

            {flash.success && <FlashBanner message={flash.success} variant="success" className="" />}
            {flash.error && <FlashBanner message={flash.error} variant="error" className="" />}

            <div className="hidden flex-col gap-10 md:flex">
                <StatSection heading={translations.sections.listings} cards={listingCards} />
                <StatSection heading={translations.sections.users} cards={userCards} />
                <StatSection heading={translations.sections.moderation} cards={moderationCards} />
            </div>

            <AdminMobileStats
                heading={translations.sections.moderation}
                moderationCards={[pendingListings, ...moderationCards]}
                otherCards={mobileOtherCards}
            />
        </AdminLayout>
    )
}
