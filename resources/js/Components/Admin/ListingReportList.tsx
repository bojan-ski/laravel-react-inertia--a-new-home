import { formatDate } from '@/Components/Listings/listingFormat'
import type { ListingReportListProps } from '@/types/listing-report'

export default function ListingReportList({ reports, translations, reasonLabels }: ListingReportListProps) {
    return (
        <ul className="border-t border-rule">
            {reports.map((report) => (
                <li key={report.id} className="flex flex-col gap-1 border-b border-rule py-4">
                    <p className="text-[15px] font-medium">{reasonLabels[report.reason] ?? report.reason}</p>
                    {report.details && (
                        <p className="text-[15px] font-book whitespace-pre-line wrap-break-word">{report.details}</p>
                    )}
                    <p className="text-[13px] text-ink-muted">
                        {translations.reported_by
                            .replace(':name', report.reportedByName ?? '')
                            .replace(':date', formatDate(report.createdAt))}
                    </p>
                </li>
            ))}
        </ul>
    )
}
