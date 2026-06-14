import { useState } from 'react'
import { router } from '@inertiajs/react'
import { Building2 } from 'lucide-react'
import RejectAgencyDialog from '@/Components/Admin/RejectAgencyDialog'
import type { AdminAgencyModerationRowProps } from '@/types/admin'

function formatSubmitted(value: string | null): string {
    if (!value) {
        return ''
    }

    return new Date(value).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    })
}

function truncate(value: string, length: number): string {
    return value.length > length ? `${value.slice(0, length)}…` : value
}

export default function AgencyModerationRow({ agency, translations }: AdminAgencyModerationRowProps) {
    const [rejectOpen, setRejectOpen] = useState(false)
    const [processing, setProcessing] = useState(false)

    function approve() {
        router.post(`/admin/agencies/${agency.id}/approve`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    const [ownerBefore, ownerAfter] = translations.owner
        .replace(':email', agency.ownerEmail ?? '')
        .split(':name')

    const contactValue = [agency.email, agency.phone].filter(Boolean).join(' • ') || '—'

    return (
        <div className="rounded-xl border border-border bg-surface p-4 sm:p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div className="min-w-0">
                    <p className="flex items-center gap-2 font-semibold text-text">
                        <Building2 className="size-4 shrink-0 text-text-muted" />
                        {agency.name}
                    </p>

                    <p className="mt-2 text-sm text-text-muted">
                        {ownerBefore}
                        <span className="font-semibold text-text">{agency.ownerName ?? ''}</span>
                        {ownerAfter}
                    </p>
                    <p className="mt-0.5 text-sm text-text-muted">
                        {translations.contact.replace(':contact', contactValue)}
                    </p>

                    {agency.description && (
                        <p className="mt-2 text-sm text-text-soft">{truncate(agency.description, 100)}</p>
                    )}

                    {agency.website && (
                        <p className="mt-0.5 text-sm text-text-muted">
                            {translations.website.split(':website')[0]}
                            <a
                                href={agency.website}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-brand underline hover:text-brand-dark"
                            >
                                {agency.website}
                            </a>
                        </p>
                    )}

                    {agency.address && (
                        <p className="mt-0.5 text-sm text-text-muted">
                            {translations.address.replace(':address', agency.address)}
                        </p>
                    )}

                    <p className="mt-2 text-sm text-text-muted">
                        {translations.submitted.replace(':date', formatSubmitted(agency.createdAt))}
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

            <RejectAgencyDialog
                open={rejectOpen}
                onClose={() => setRejectOpen(false)}
                agencyId={agency.id}
                agencyName={agency.name}
                translations={translations}
            />
        </div>
    )
}
