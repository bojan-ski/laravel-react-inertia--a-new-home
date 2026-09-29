import { formatDateTime } from '@/Components/Listings/listingFormat'
import type { ListingReportListProps } from '@/types/listing-report'

export default function ListingReportList({ reports, translations, reasonLabels }: ListingReportListProps) {
    return (
        <ul className="flex flex-col gap-3">
            {reports.map((report) => (
                <li key={report.id} className="rounded-lg bg-surface-muted px-4 py-3 text-sm">
                    <p className="font-semibold text-text">{reasonLabels[report.reason] ?? report.reason}</p>
                    {report.details && (
                        <p className="mt-1 whitespace-pre-line wrap-break-word text-text-soft">{report.details}</p>
                    )}
                    <p className="mt-1.5 text-xs text-text-muted">
                        {translations.reported_by
                            .replace(':name', report.reportedByName ?? '')
                            .replace(':date', formatDateTime(report.createdAt))}
                    </p>
                </li>
            ))}
        </ul>
    )
}
