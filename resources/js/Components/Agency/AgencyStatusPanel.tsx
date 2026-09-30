import { formatDate } from '@/Components/Listings/listingFormat'
import type { AgencyStatusPanelProps, AgencyStatusValue } from '@/types/agency'

const palettes: Record<AgencyStatusValue, { panel: string; heading: string; marker: string }> = {
    approved: {
        panel: 'bg-forest-50 text-forest-900',
        heading: 'text-forest-800',
        marker: 'border-forest bg-forest',
    },
    pending_verification: {
        panel: 'bg-sand text-ink',
        heading: '',
        marker: 'border-ink',
    },
    rejected: {
        panel: 'border border-ink bg-sand text-ink',
        heading: '',
        marker: 'border-ink bg-ink',
    },
    hidden: {
        panel: 'bg-sand text-ink',
        heading: '',
        marker: 'border-ink bg-ink',
    },
}

export default function AgencyStatusPanel({ agency, translations }: AgencyStatusPanelProps) {
    const copy = translations.status[agency.status]
    const palette = palettes[agency.status]

    const extraLine = {
        pending_verification: copy.note ?? null,
        approved:
            copy.approved_on && agency.approvedAt
                ? copy.approved_on.replace(':date', formatDate(agency.approvedAt, 'long'))
                : null,
        rejected:
            copy.reason && agency.rejectionReason ? copy.reason.replace(':reason', agency.rejectionReason) : null,
        hidden: null,
    }[agency.status]

    return (
        <div
            className={`flex flex-col gap-1.5 p-4.5 md:grid md:grid-cols-[220px_minmax(0,1fr)] md:gap-8 md:px-8 md:py-7 ${palette.panel}`}
        >
            <div className="flex items-center gap-3">
                <span aria-hidden="true" className={`hidden size-2.5 shrink-0 border md:block ${palette.marker}`} />
                <h2 className={`text-[22px] leading-tight font-light tracking-[-0.015em] md:text-[26px] ${palette.heading}`}>
                    {copy.heading}
                </h2>
            </div>

            <div className="flex flex-col gap-1.5">
                <p className="text-sm/normal md:text-[15px]/[1.55]">{copy.body}</p>
                {extraLine && <p className="text-xs opacity-80 md:text-[13px]">{extraLine}</p>}
            </div>
        </div>
    )
}
