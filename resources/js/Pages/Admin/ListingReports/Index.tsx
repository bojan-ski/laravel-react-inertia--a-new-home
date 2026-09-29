import { Head, usePage } from '@inertiajs/react'
import { CheckCircle2 } from 'lucide-react'
import ListingReportModerationRow from '@/Components/Admin/ListingReportModerationRow'
import FlashBanner from '@/Components/UI/FlashBanner'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminListingReportsPageProps } from '@/types/listing-report'
import type { SharedProps } from '@/types/shared'

export default function Index({ listings, translations, reasonLabels }: AdminListingReportsPageProps) {
    const { flash } = usePage<SharedProps>().props

    return (
        <AdminLayout>
            <Head title={translations.title} />

            {flash.success && <FlashBanner message={flash.success} variant="success" />}
            {flash.error && <FlashBanner message={flash.error} variant="error" />}

            <header>
                <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                    {translations.title}
                </h1>
                <p className="mt-2 text-text-soft">{translations.subtitle}</p>
            </header>

            {listings.data.length > 0 ? (
                <div className="mt-8 flex flex-col gap-4">
                    {listings.data.map((listing) => (
                        <ListingReportModerationRow
                            key={listing.id}
                            listing={listing}
                            translations={translations}
                            reasonLabels={reasonLabels}
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
