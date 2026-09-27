import { useState } from 'react'
import { Link, router } from '@inertiajs/react'
import { Building2, ExternalLink } from 'lucide-react'
import RejectAgencyDialog from '@/Components/Admin/RejectAgencyDialog'
import { formatDateTime } from '@/Components/Listings/listingFormat'
import type { AgencyChangeRequestRowProps } from '@/types/admin'

export default function AgencyChangeRequestRow({
    change,
    translations,
    ownerTemplate,
    fieldLabels,
}: AgencyChangeRequestRowProps) {
    const [rejectOpen, setRejectOpen] = useState(false)
    const [processing, setProcessing] = useState(false)
    const agencyName = change.agencyName ?? ''

    function approve() {
        router.post(`/admin/agencies/${change.agencyId}/changes/approve`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    return (
        <div className="rounded-xl border border-border bg-surface p-4 sm:p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div className="min-w-0">
                    <p className="flex items-center gap-2 font-semibold text-text">
                        <Building2 className="size-4 shrink-0 text-text-muted" />
                        {agencyName}
                        {change.agencySlug && (
                            <Link
                                href={`/agencies/${change.agencySlug}`}
                                className="text-text-muted transition-colors hover:text-brand"
                                title={translations.view_public}
                                aria-label={translations.view_public}
                            >
                                <ExternalLink className="size-4" />
                            </Link>
                        )}
                    </p>
                    <p className="mt-1 text-sm text-text-muted">
                        {ownerTemplate.replace(':name', change.ownerName ?? '').replace(':email', change.ownerEmail ?? '')}
                    </p>
                    <p className="mt-0.5 text-sm text-text-muted">
                        {translations.submitted.replace(':date', formatDateTime(change.submittedAt))}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3 md:shrink-0">
                    <button
                        type="button"
                        onClick={approve}
                        disabled={processing}
                        className="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-50"
                    >
                        {translations.actions.approve}
                    </button>
                    <button
                        type="button"
                        onClick={() => setRejectOpen(true)}
                        disabled={processing}
                        className="rounded-lg border border-status-rejected px-4 py-2 text-sm font-semibold text-status-rejected transition-colors hover:bg-status-rejected/10 disabled:opacity-50"
                    >
                        {translations.actions.reject}
                    </button>
                </div>
            </div>

            <div className="mt-4 overflow-x-auto">
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-border text-xs uppercase tracking-wide text-text-muted">
                            <th className="py-2 pr-4 font-medium">{translations.field}</th>
                            <th className="py-2 pr-4 font-medium">{translations.current}</th>
                            <th className="py-2 font-medium">{translations.proposed}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {change.changes.map((diff) => (
                            <tr key={diff.field} className="border-b border-border last:border-b-0 align-top">
                                <td className="py-2 pr-4 font-medium text-text">{fieldLabels[diff.field] ?? diff.field}</td>
                                <td className="whitespace-pre-line py-2 pr-4 text-text-muted line-through decoration-status-rejected/50">
                                    {diff.current ?? translations.empty_value}
                                </td>
                                <td className="whitespace-pre-line py-2 text-text">
                                    {diff.proposed ?? translations.empty_value}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <RejectAgencyDialog
                open={rejectOpen}
                onClose={() => setRejectOpen(false)}
                actionUrl={`/admin/agencies/${change.agencyId}/changes/reject`}
                subjectName={agencyName}
                dialog={translations.dialog}
            />
        </div>
    )
}
