import { AlertCircle, CheckCircle2, EyeOff, Info } from 'lucide-react'
import type { ComponentType } from 'react'
import { formatDate } from '@/Components/Listings/listingFormat'
import type { AgencyStatusPanelProps, AgencyStatusValue } from '@/types/agency'

const statusStyles: Record<AgencyStatusValue, { container: string; icon: ComponentType<{ className?: string }>; iconColor: string }> = {
    pending_verification: {
        container: 'border-accent bg-accent-soft',
        icon: Info,
        iconColor: 'text-accent',
    },
    approved: {
        container: 'border-brand bg-brand-soft',
        icon: CheckCircle2,
        iconColor: 'text-brand',
    },
    rejected: {
        container: 'border-status-rejected bg-status-rejected/10',
        icon: AlertCircle,
        iconColor: 'text-status-rejected',
    },
    hidden: {
        container: 'border-border-strong bg-surface-muted',
        icon: EyeOff,
        iconColor: 'text-text-muted',
    },
}

export default function AgencyStatusPanel({ agency, translations }: AgencyStatusPanelProps) {
    const copy = translations.status[agency.status]
    const style = statusStyles[agency.status]
    const Icon = style.icon

    const approvedDate = agency.approvedAt ? formatDate(agency.approvedAt, 'long') : null

    return (
        <div className={`flex items-start gap-4 rounded-xl border px-5 py-5 ${style.container}`}>
            <Icon className={`mt-0.5 size-6 shrink-0 ${style.iconColor}`} />

            <div className="min-w-0">
                <h2 className="font-display text-xl font-semibold text-text">{copy.heading}</h2>
                <p className="mt-1 text-text">{copy.body}</p>

                {agency.status === 'pending_verification' && copy.note && (
                    <p className="mt-2 text-sm text-text-soft">{copy.note}</p>
                )}

                {agency.status === 'approved' && approvedDate && copy.approved_on && (
                    <p className="mt-2 text-sm text-text-soft">
                        {copy.approved_on.replace(':date', approvedDate)}
                    </p>
                )}

                {agency.status === 'rejected' && agency.rejectionReason && (
                    <blockquote className="mt-3 border-l-2 border-status-rejected pl-3 text-sm italic text-text-soft">
                        {agency.rejectionReason}
                    </blockquote>
                )}
            </div>
        </div>
    )
}
