import { Head, usePage } from '@inertiajs/react'
import { CheckCircle2 } from 'lucide-react'
import ListingModerationRow from '@/Components/Admin/ListingModerationRow'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminListingsPageProps } from '@/types/admin'
import type { SharedProps } from '@/types/shared'

export default function Index({ listings, translations, propertyTypeLabels }: AdminListingsPageProps) {
    const { flash } = usePage<SharedProps>().props

    return (
        <AdminLayout>
            <Head title={translations.title} />

            {flash.success && (
                <div className="mb-6 rounded-lg border border-status-approved/30 bg-status-approved/10 px-4 py-3 text-sm text-text">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="mb-6 rounded-lg border border-status-rejected/30 bg-status-rejected/10 px-4 py-3 text-sm text-text">
                    {flash.error}
                </div>
            )}

            <header>
                <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                    {translations.title}
                </h1>
                <p className="mt-2 text-text-soft">{translations.subtitle}</p>
            </header>

            {listings.data.length > 0 ? (
                <div className="mt-8 flex flex-col gap-4">
                    {listings.data.map((listing) => (
                        <ListingModerationRow
                            key={listing.id}
                            listing={listing}
                            translations={translations}
                            propertyTypeLabels={propertyTypeLabels}
                        />
                    ))}
                </div>
            ) : (
                <div className="mt-10 flex flex-col items-center gap-3 py-20 text-center">
                    <CheckCircle2 className="size-10 text-text-muted" />
                    <p className="font-display text-2xl font-semibold text-text">
                        {translations.empty.title}
                    </p>
                    <p className="text-text-soft">{translations.empty.subtitle}</p>
                </div>
            )}

            <Pagination paginator={listings} labels={translations.pagination} />
        </AdminLayout>
    )
}
