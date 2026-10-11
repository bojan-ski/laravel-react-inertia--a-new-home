import { Head } from '@inertiajs/react'
import AdminPageHeader from '@/Components/Admin/AdminPageHeader'
import ListingModerationRow from '@/Components/Admin/ListingModerationRow'
import EmptyState from '@/Components/UI/EmptyState'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminListingsPageProps } from '@/types/admin'

export default function Index({ listings, translations, propertyTypeLabels }: AdminListingsPageProps) {

    return (
        <AdminLayout>
            <Head title={translations.title} />

            <AdminPageHeader title={translations.title} subtitle={translations.subtitle} />

            <div>
                {listings.data.length > 0 ? (
                    <ul className="border-t border-rule">
                        {listings.data.map((listing) => (
                            <ListingModerationRow
                                key={listing.id}
                                listing={listing}
                                translations={translations}
                                propertyTypeLabels={propertyTypeLabels}
                            />
                        ))}
                    </ul>
                ) : (
                    <EmptyState title={translations.empty.title} hint={translations.empty.subtitle} />
                )}

                <Pagination paginator={listings} labels={translations.pagination} className="mt-8" />
            </div>
        </AdminLayout>
    )
}
