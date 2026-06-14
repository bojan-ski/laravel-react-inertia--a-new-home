import { useState } from 'react'
import { router } from '@inertiajs/react'
import { Trash2 } from 'lucide-react'
import ConfirmDeleteUserDialog from '@/Components/Admin/ConfirmDeleteUserDialog'
import StatusBadge from '@/Components/UI/StatusBadge'
import type { AdminUserRowProps } from '@/types/admin'

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}

export default function AdminUserRow({ user, translations }: AdminUserRowProps) {
    const [confirmOpen, setConfirmOpen] = useState(false)
    const [processing, setProcessing] = useState(false)

    function act(action: 'suspend' | 'reactivate') {
        router.post(`/admin/users/${user.id}/${action}`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    return (
        <div className="rounded-xl border border-border bg-surface p-4 sm:p-5">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex min-w-0 gap-4">
                    <div className="flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-muted font-mono text-lg font-semibold text-text-soft">
                        {user.name.charAt(0).toUpperCase()}
                    </div>
                    <div className="min-w-0">
                        <p className="font-semibold text-text">{user.name}</p>
                        <p className="truncate text-sm text-text-muted">{user.email}</p>
                        <p className="mt-1 text-xs text-text-muted">
                            {translations.joined.replace(':date', formatDate(user.createdAt))}
                        </p>
                        {user.suspendedAt && (
                            <p className="mt-0.5 text-xs text-status-suspended">
                                {translations.suspended_on.replace(':date', formatDate(user.suspendedAt))}
                            </p>
                        )}
                        {user.agency && (
                            <p className="mt-0.5 text-xs text-text-muted">
                                {translations.agency_line
                                    .replace(':name', user.agency.name)
                                    .replace(':status', user.agency.status)}
                            </p>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-3 sm:flex-col sm:items-end">
                    <div className="flex items-center gap-2">
                        <span className="inline-flex items-center rounded-full bg-surface-muted px-2.5 py-0.5 text-xs font-medium uppercase tracking-wide text-text">
                            {translations.roles[user.role]}
                        </span>
                        <StatusBadge status={user.status} label={translations.statuses[user.status]} />
                    </div>

                    <div className="flex items-center gap-2">
                        {user.can.canSuspend && (
                            <button
                                type="button"
                                onClick={() => act('suspend')}
                                disabled={processing}
                                className="rounded-lg border border-border px-3 py-1.5 text-sm font-medium text-text-soft transition-colors hover:border-status-rejected hover:text-status-rejected disabled:opacity-50"
                            >
                                {translations.actions.suspend}
                            </button>
                        )}
                        {user.can.canReactivate && (
                            <button
                                type="button"
                                onClick={() => act('reactivate')}
                                disabled={processing}
                                className="rounded-lg border border-border px-3 py-1.5 text-sm font-medium text-text-soft transition-colors hover:border-status-approved hover:text-status-approved disabled:opacity-50"
                            >
                                {translations.actions.reactivate}
                            </button>
                        )}
                        {user.can.canDelete && (
                            <button
                                type="button"
                                onClick={() => setConfirmOpen(true)}
                                aria-label={translations.actions.delete}
                                className="rounded-lg p-2 text-text-muted transition-colors hover:bg-status-rejected/10 hover:text-status-rejected"
                            >
                                <Trash2 className="size-4" />
                            </button>
                        )}
                    </div>
                </div>
            </div>

            <ConfirmDeleteUserDialog
                open={confirmOpen}
                onClose={() => setConfirmOpen(false)}
                userId={user.id}
                userName={user.name}
                userEmail={user.email}
                translations={translations}
            />
        </div>
    )
}
