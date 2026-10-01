import { useState } from 'react'
import { router } from '@inertiajs/react'
import { formatDate } from '@/Components/Listings/listingFormat'
import Button from '@/Components/UI/Button'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import Kicker from '@/Components/UI/Kicker'
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
        <li className="flex flex-col gap-6 border-t border-rule pt-8 lg:grid lg:grid-cols-[minmax(0,1fr)_300px] lg:gap-12">
            <div className="flex min-w-0 flex-col gap-6">
                <div className="flex flex-col gap-1">
                    <Kicker>{translations.listing_context}</Kicker>
                    <p className="text-lg wrap-break-word">{report.listingTitle ?? '—'}</p>
                    {report.conversationId !== null && (
                        <p className="text-[13px] text-ink-muted">
                            {translations.conversation.replace(':id', String(report.conversationId))}
                        </p>
                    )}
                </div>

                <div className="flex flex-col gap-2.5">
                    <Kicker>{translations.reported_message}</Kicker>
                    <blockquote className="border-l border-ink pl-5 text-xl leading-[1.4] font-airy whitespace-pre-line wrap-break-word lg:text-[26px]">
                        {report.messageBody ?? '—'}
                    </blockquote>
                    {report.messageSenderName && (
                        <p className="pl-5.25 text-[13px] text-ink-muted">
                            {translations.sent_by.replace(':name', report.messageSenderName)}
                        </p>
                    )}
                </div>

                <div className="flex flex-col gap-1.5 bg-sand px-5 py-4.5">
                    <p className="text-xs text-ink-muted">{translations.reason_label}</p>
                    <p className="text-[15px] leading-[1.55] whitespace-pre-line wrap-break-word">{report.reason}</p>
                    <p className="text-[13px] text-ink-muted">
                        {translations.reported_by
                            .replace(':name', report.reportedByName ?? '')
                            .replace(':date', formatDate(report.createdAt))}
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-2 gap-2.5 border-t border-ink pt-5 lg:sticky lg:top-8 lg:flex lg:flex-col lg:self-start">
                <Button variant="secondary" size="sm" onClick={resolve} disabled={processing} className="w-full">
                    {translations.actions.resolve}
                </Button>
                <Button size="sm" onClick={() => setSuspendOpen(true)} disabled={processing} className="w-full">
                    {translations.actions.suspend}
                </Button>
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
        </li>
    )
}
