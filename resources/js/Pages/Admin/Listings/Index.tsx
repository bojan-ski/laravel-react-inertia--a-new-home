import { Head, usePage } from '@inertiajs/react'
import AdminPageHeader from '@/Components/Admin/AdminPageHeader'
import ListingModerationRow from '@/Components/Admin/ListingModerationRow'
import EmptyState from '@/Components/UI/EmptyState'
import FlashBanner from '@/Components/UI/FlashBanner'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminListingsPageProps } from '@/types/admin'
import type { SharedProps } from '@/types/shared'

export default function Index({ listings, translations, propertyTypeLabels }: AdminListingsPageProps) {
    const { flash } = usePage<SharedProps>().props

    return (
        <AdminLayout>
            <Head title={translations.title} />

            <AdminPageHeader title={translations.title} subtitle={translations.subtitle} />

            {flash.success && <FlashBanner message={flash.success} variant="success" className="" />}
            {flash.error && <FlashBanner message={flash.error} variant="error" className="" />}

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
