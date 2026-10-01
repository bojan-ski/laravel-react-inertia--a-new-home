import { useState } from 'react'
import { router } from '@inertiajs/react'
import { formatDate, formatDateTime } from '@/Components/Listings/listingFormat'
import Button from '@/Components/UI/Button'
import ConfirmDialog from '@/Components/UI/ConfirmDialog'
import StatusBadge from '@/Components/UI/StatusBadge'
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

    const meta = [
        translations.from.replace(':email', message.email),
        message.senderName ? translations.account.replace(':name', message.senderName) : translations.guest,
        translations.received.replace(':date', formatDateTime(message.createdAt)),
    ].join(' · ')

    return (
        <li className="flex flex-col gap-4 border-b border-rule py-7 transition-colors duration-150 hover:bg-sand/55 md:grid md:grid-cols-[minmax(0,1fr)_auto] md:gap-8">
            <div className="flex min-w-0 flex-col gap-2">
                <div className="flex flex-wrap items-center gap-3">
                    <h2 className="text-xl leading-tight font-light tracking-[-0.015em] wrap-break-word md:text-2xl">
                        {message.subject}
                    </h2>
                    {message.isHandled && <StatusBadge status="handled" label={translations.handled_badge} />}
                </div>
                <p className="text-[13px] wrap-break-word text-ink-muted">{meta}</p>
                {message.isHandled && message.handledAt && (
                    <p className="text-[13px] text-ink-muted">
                        {translations.handled_by
                            .replace(':name', message.handledByName ?? '—')
                            .replace(':date', formatDate(message.handledAt))}
                    </p>
                )}
                <p className="mt-1.5 max-w-180 text-[15px] leading-[1.6] font-book whitespace-pre-line wrap-break-word">
                    {message.message}
                </p>
            </div>

            <div className="flex flex-wrap items-start gap-2.5">
                <Button
                    variant="link"
                    onClick={() => setDeleteOpen(true)}
                    disabled={processing}
                    className="min-h-11 px-2"
                >
                    {translations.actions.delete}
                </Button>
                {!message.isHandled && (
                    <>
                        <Button variant="secondary" size="sm" href={replyHref} external>
                            {translations.actions.reply}
                        </Button>
                        <Button size="sm" onClick={markHandled} disabled={processing}>
                            {translations.actions.mark_handled}
                        </Button>
                    </>
                )}
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
        </li>
    )
}
