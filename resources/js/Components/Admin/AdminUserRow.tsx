import { useState } from 'react'
import { router } from '@inertiajs/react'
import ConfirmDeleteUserDialog from '@/Components/Admin/ConfirmDeleteUserDialog'
import { formatDate } from '@/Components/Listings/listingFormat'
import StatusBadge from '@/Components/UI/StatusBadge'
import type { AdminUserRowProps } from '@/types/admin'

const cellClasses = 'max-xl:block xl:py-4.5 xl:pr-6 xl:align-middle'
const textActionClasses =
    'min-h-11 transition-colors duration-150 disabled:cursor-not-allowed disabled:opacity-45 xl:min-h-0'

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

    const dateLine = user.suspendedAt
        ? translations.suspended_on.replace(':date', formatDate(user.suspendedAt))
        : translations.joined.replace(':date', formatDate(user.createdAt))

    return (
        <tr className="border-b border-rule transition-colors duration-150 max-xl:flex max-xl:flex-wrap max-xl:items-center max-xl:gap-x-3 max-xl:gap-y-2 max-xl:py-5 xl:hover:bg-sand/55">
            <td className={`${cellClasses} max-xl:basis-full`}>
                <div className="flex flex-col gap-0.75">
                    <span className="text-base wrap-break-word">{user.name}</span>
                    <span className="text-[13px] wrap-break-word text-ink-muted">{user.email}</span>
                </div>
            </td>
            <td className={`${cellClasses} text-sm`}>{translations.roles[user.role]}</td>
            <td className={cellClasses}>
                <StatusBadge status={user.status} label={translations.statuses[user.status]} />
            </td>
            <td className={`${cellClasses} max-xl:basis-full`}>
                <div className="flex flex-col gap-0.75 text-[13px] text-ink-muted">
                    <span>{dateLine}</span>
                    {user.agency && (
                        <span className="wrap-break-word">
                            {translations.agency_line
                                .replace(':name', user.agency.name)
                                .replace(':status', translations.agency_statuses[user.agency.status])}
                        </span>
                    )}
                </div>
            </td>
            <td className="max-xl:block max-xl:basis-full xl:py-4.5 xl:align-middle">
                <div className="flex items-center gap-5 text-sm xl:justify-end">
                    {user.can.canSuspend && (
                        <button
                            type="button"
                            onClick={() => act('suspend')}
                            disabled={processing}
                            className={`${textActionClasses} hover:text-forest`}
                        >
                            <span className="border-b border-current">{translations.actions.suspend}</span>
                        </button>
                    )}
                    {user.can.canReactivate && (
                        <button
                            type="button"
                            onClick={() => act('reactivate')}
                            disabled={processing}
                            className={`${textActionClasses} hover:text-forest`}
                        >
                            <span className="border-b border-current">{translations.actions.reactivate}</span>
                        </button>
                    )}
                    {user.can.canDelete && (
                        <button
                            type="button"
                            onClick={() => setConfirmOpen(true)}
                            disabled={processing}
                            className={`${textActionClasses} text-ink-muted hover:text-ink`}
                        >
                            {translations.actions.delete}
                        </button>
                    )}
                </div>

                <ConfirmDeleteUserDialog
                    open={confirmOpen}
                    onClose={() => setConfirmOpen(false)}
                    userId={user.id}
                    userName={user.name}
                    userEmail={user.email}
                    translations={translations}
                />
            </td>
        </tr>
    )
}
