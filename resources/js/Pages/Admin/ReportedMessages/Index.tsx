import { Head, usePage } from '@inertiajs/react'
import AdminPageHeader from '@/Components/Admin/AdminPageHeader'
import ReportedMessageModerationRow from '@/Components/Admin/ReportedMessageModerationRow'
import EmptyState from '@/Components/UI/EmptyState'
import FlashBanner from '@/Components/UI/FlashBanner'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminReportedMessagesPageProps } from '@/types/reported-message'
import type { SharedProps } from '@/types/shared'

export default function Index({ reports, translations }: AdminReportedMessagesPageProps) {
    const { flash } = usePage<SharedProps>().props

    return (
        <AdminLayout>
            <Head title={translations.title} />

            <AdminPageHeader title={translations.title} subtitle={translations.subtitle} />

            {flash.success && <FlashBanner message={flash.success} variant="success" className="" />}
            {flash.error && <FlashBanner message={flash.error} variant="error" className="" />}

            <div>
                {reports.data.length > 0 ? (
                    <ul className="flex flex-col gap-12">
                        {reports.data.map((report) => (
                            <ReportedMessageModerationRow
                                key={report.id}
                                report={report}
                                translations={translations}
                            />
                        ))}
                    </ul>
                ) : (
                    <EmptyState title={translations.empty.title} hint={translations.empty.subtitle} />
                )}

                <Pagination paginator={reports} labels={translations.pagination} className="mt-12" />
            </div>
        </AdminLayout>
    )
}
