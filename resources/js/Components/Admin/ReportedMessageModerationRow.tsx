import { useState } from 'react'
import { router } from '@inertiajs/react'
import { formatDateTime } from '@/Components/Listings/listingFormat'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import type { ReportedMessageModerationRowProps } from '@/types/reported-message'

export default function ReportedMessageModerationRow({
    report,
    translations,
}: ReportedMessageModerationRowProps) {
    const [suspendOpen, setSuspendOpen] = useState(false)
    const [processing, setProcessing] = useState(false)

    function resolve() {
        router.post(`/admin/reported-messages/${report.id}/resolve`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    function suspend() {
        router.post(`/admin/reported-messages/${report.id}/suspend`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false)
                setSuspendOpen(false)
            },
        })
    }

    const offenderName = report.offendingUserName ?? ''

    return (
        <div className="rounded-xl border border-border bg-surface p-4 sm:p-5">
            <div className="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                <div className="min-w-0 flex-1">
                    <p className="text-xs font-medium uppercase tracking-wider text-text-muted">
                        {translations.listing_context}
                    </p>
                    <p className="mt-1 font-semibold text-text">{report.listingTitle ?? '—'}</p>
                    {report.conversationId !== null && (
                        <p className="mt-0.5 text-sm text-text-muted">
                            {translations.conversation.replace(':id', String(report.conversationId))}
                        </p>
                    )}

                    <p className="mt-4 text-xs font-medium uppercase tracking-wider text-text-muted">
                        {translations.reported_message}
                    </p>
                    <blockquote className="mt-1 border-l-4 border-status-rejected bg-surface-muted px-4 py-3 text-sm text-text">
                        {report.messageBody ?? '—'}
                    </blockquote>
                    <p className="mt-1.5 text-sm text-text-muted">
                        {translations.sent_by.replace(':name', report.messageSenderName ?? '')}
                    </p>

                    <p className="mt-4 text-xs font-medium uppercase tracking-wider text-text-muted">
                        {translations.reason_label}
                    </p>
                    <p className="mt-1 rounded-lg bg-surface-muted px-4 py-3 text-sm text-text">
                        {report.reason}
                    </p>
                    <p className="mt-1.5 text-sm text-text-muted">
                        {translations.reported_by
                            .replace(':name', report.reportedByName ?? '')
                            .replace(':date', formatDateTime(report.createdAt))}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3 md:shrink-0 md:flex-col md:items-stretch">
                    <button
                        type="button"
                        onClick={resolve}
                        disabled={processing}
                        className="rounded-lg border border-border-strong px-4 py-2 text-sm font-semibold text-text transition-colors hover:border-text disabled:opacity-50"
                    >
                        {translations.actions.resolve}
                    </button>
                    <button
                        type="button"
                        onClick={() => setSuspendOpen(true)}
                        disabled={processing}
                        className="rounded-lg border border-status-rejected px-4 py-2 text-sm font-semibold text-status-rejected transition-colors hover:bg-status-rejected/10 disabled:opacity-50"
                    >
                        {translations.actions.suspend}
                    </button>
                </div>
            </div>

            <ConfirmDialog
                open={suspendOpen}
                title={translations.confirm.title.replace(':name', offenderName)}
                description={translations.confirm.description}
                confirmLabel={translations.confirm.confirm}
                cancelLabel={translations.confirm.cancel}
                processing={processing}
                onConfirm={suspend}
                onCancel={() => setSuspendOpen(false)}
            />
        </div>
    )
}
