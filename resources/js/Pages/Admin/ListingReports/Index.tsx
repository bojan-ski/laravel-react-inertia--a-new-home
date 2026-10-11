import { Head } from '@inertiajs/react'
import AdminPageHeader from '@/Components/Admin/AdminPageHeader'
import ListingReportModerationRow from '@/Components/Admin/ListingReportModerationRow'
import EmptyState from '@/Components/UI/EmptyState'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminListingReportsPageProps } from '@/types/listing-report'

export default function Index({ listings, translations, reasonLabels }: AdminListingReportsPageProps) {

    return (
        <AdminLayout>
            <Head title={translations.title} />

            <AdminPageHeader title={translations.title} subtitle={translations.subtitle} />

            <div>
                {listings.data.length > 0 ? (
                    <ul className="flex flex-col gap-12">
                        {listings.data.map((listing) => (
                            <ListingReportModerationRow
                                key={listing.id}
                                listing={listing}
                                translations={translations}
                                reasonLabels={reasonLabels}
                            />
                        ))}
                    </ul>
                ) : (
                    <EmptyState title={translations.empty.title} hint={translations.empty.subtitle} />
                )}

                <Pagination paginator={listings} labels={translations.pagination} className="mt-12" />
            </div>
        </AdminLayout>
    )
}
