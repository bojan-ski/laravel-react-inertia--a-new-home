import ListingReportActions from '@/Components/Admin/ListingReportActions'
import ListingReportList from '@/Components/Admin/ListingReportList'
import { countLabel } from '@/Components/Listings/listingFormat'
import FlashBanner from '@/Components/UI/FlashBanner'
import type { ListingReportPanelProps } from '@/types/admin'

export default function ListingReportPanel({ report, translations, reasonLabels }: ListingReportPanelProps) {
    return (
        <section className="flex flex-col gap-5 border-t border-ink pt-6">
            <div className="flex flex-wrap items-baseline gap-x-4 gap-y-1">
                <h2 className="text-[26px] leading-tight font-airy tracking-[-0.02em] lg:text-[32px]">
                    {translations.panel_heading}
                </h2>
                <span className="text-sm text-ink-muted">
                    {countLabel(
                        report.openReportsCount,
                        translations.reports_count_one,
                        translations.reports_count_other,
                    )}
                </span>
            </div>

            <ListingReportList reports={report.reports} translations={translations} reasonLabels={reasonLabels} />

            {!report.canTakeDown && <FlashBanner message={translations.not_approved} variant="info" className="" />}

            <div className="flex flex-wrap items-center gap-2.5">
                <ListingReportActions listing={report} translations={translations} />
            </div>
        </section>
    )
}
