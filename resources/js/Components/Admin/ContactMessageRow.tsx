import { useState } from 'react'
import { router } from '@inertiajs/react'
import { formatDateTime } from '@/Components/Listings/listingFormat'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import type { ContactMessageRowProps } from '@/types/contact'

export default function ContactMessageRow({ message, translations }: ContactMessageRowProps) {
    const [deleteOpen, setDeleteOpen] = useState(false)
    const [processing, setProcessing] = useState(false)

    function markHandled() {
        router.post(`/admin/contact-messages/${message.id}/handled`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    function destroy() {
        router.delete(`/admin/contact-messages/${message.id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false)
                setDeleteOpen(false)
            },
        })
    }

    const replyHref = `mailto:${message.email}?subject=${encodeURIComponent(translations.reply_subject.replace(':subject', message.subject))}`

    return (
        <div className="rounded-xl border border-border bg-surface p-4 sm:p-5">
            <div className="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="font-semibold text-text">{message.subject}</p>
                        {message.isHandled && (
                            <span className="rounded-full bg-status-approved/10 px-2.5 py-0.5 text-xs font-medium uppercase tracking-wide text-status-approved">
                                {translations.handled_badge}
                            </span>
                        )}
                    </div>

                    <p className="mt-1 text-sm text-text-muted">
                        {translations.from.replace(':email', message.email)}
                        {' • '}
                        {message.senderName ? translations.account.replace(':name', message.senderName) : translations.guest}
                    </p>

                    <p className="mt-4 whitespace-pre-line rounded-lg bg-surface-muted px-4 py-3 text-sm leading-relaxed text-text">
                        {message.message}
                    </p>

                    <p className="mt-1.5 text-sm text-text-muted">
                        {translations.received.replace(':date', formatDateTime(message.createdAt))}
                    </p>

                    {message.isHandled && message.handledAt && (
                        <p className="mt-0.5 text-sm text-text-muted">
                            {translations.handled_by
                                .replace(':name', message.handledByName ?? '—')
                                .replace(':date', formatDateTime(message.handledAt))}
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-3 md:shrink-0 md:flex-col md:items-stretch">
                    <a
                        href={replyHref}
                        className="rounded-lg bg-brand px-4 py-2 text-center text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                    >
                        {translations.actions.reply}
                    </a>
                    {!message.isHandled && (
                        <button
                            type="button"
                            onClick={markHandled}
                            disabled={processing}
                            className="rounded-lg border border-border-strong px-4 py-2 text-sm font-semibold text-text transition-colors hover:border-text disabled:opacity-50"
                        >
                            {translations.actions.mark_handled}
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => setDeleteOpen(true)}
                        disabled={processing}
                        className="rounded-lg border border-status-rejected px-4 py-2 text-sm font-semibold text-status-rejected transition-colors hover:bg-status-rejected/10 disabled:opacity-50"
                    >
                        {translations.actions.delete}
                    </button>
                </div>
            </div>

            <ConfirmDialog
                open={deleteOpen}
                title={translations.confirm.title}
                description={translations.confirm.description}
                confirmLabel={translations.confirm.confirm}
                cancelLabel={translations.confirm.cancel}
                processing={processing}
                onConfirm={destroy}
                onCancel={() => setDeleteOpen(false)}
            />
        </div>
    )
}
