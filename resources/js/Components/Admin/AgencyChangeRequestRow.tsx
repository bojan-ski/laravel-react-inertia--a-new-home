import { useState } from 'react'
import { Link, router } from '@inertiajs/react'
import RejectAgencyDialog from '@/Components/Admin/RejectAgencyDialog'
import { formatDate } from '@/Components/Listings/listingFormat'
import Button from '@/Components/UI/Button'
import type { AgencyChangeRequestRowProps } from '@/types/admin'

const cellClasses = 'border-b border-rule py-3 align-top whitespace-pre-line wrap-break-word max-md:block'
const mobileLabelClasses = 'mb-1 block text-[11px] tracking-widest text-ink-muted uppercase md:hidden'

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
        <article className="flex flex-col gap-5 border border-rule p-5 md:p-7">
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between md:gap-6">
                <div className="flex min-w-0 flex-col gap-1">
                    <h3 className="text-2xl leading-tight font-light tracking-[-0.015em] wrap-break-word">
                        {agencyName}
                    </h3>
                    <p className="text-[13px] wrap-break-word text-ink-muted">
                        {ownerTemplate
                            .replace(':name', change.ownerName ?? '')
                            .replace(':email', change.ownerEmail ?? '')}
                    </p>
                    <p className="text-[13px] text-ink-muted">
                        {translations.submitted.replace(':date', formatDate(change.submittedAt))}
                        {change.agencySlug && (
                            <>
                                <span aria-hidden="true"> · </span>
                                <Link
                                    href={`/agencies/${change.agencySlug}`}
                                    className="border-b border-current transition-colors duration-150 hover:text-forest"
                                >
                                    {translations.view_public}
                                </Link>
                            </>
                        )}
                    </p>
                </div>

                <div className="flex shrink-0 items-start gap-2.5">
                    <Button variant="secondary" size="sm" onClick={() => setRejectOpen(true)} disabled={processing}>
                        {translations.actions.reject}
                    </Button>
                    <Button size="sm" onClick={approve} disabled={processing}>
                        {translations.actions.approve}
                    </Button>
                </div>
            </div>

            <table className="w-full table-fixed text-left text-sm max-md:block">
                <colgroup>
                    <col className="w-55" />
                    <col />
                    <col />
                </colgroup>
                <thead className="max-md:sr-only">
                    <tr>
                        {[translations.field, translations.current, translations.proposed].map((heading, index) => (
                            <th
                                key={heading}
                                scope="col"
                                className={`border-b border-ink py-2.5 text-xs font-normal tracking-widest text-ink-muted uppercase ${
                                    index === 2 ? 'px-3' : 'pr-4'
                                }`}
                            >
                                {heading}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="max-md:block">
                    {change.changes.map((diff) => (
                        <tr key={diff.field} className="max-md:block max-md:border-b max-md:border-rule max-md:py-3">
                            <th
                                scope="row"
                                className={`${cellClasses} pr-4 font-normal text-ink-muted max-md:border-0 max-md:py-0 max-md:pb-2`}
                            >
                                {fieldLabels[diff.field] ?? diff.field}
                            </th>
                            <td className={`${cellClasses} pr-4 max-md:border-0 max-md:py-0 max-md:pb-2`}>
                                <span className={mobileLabelClasses}>{translations.current}</span>
                                {diff.current ?? translations.empty_value}
                            </td>
                            <td
                                className={`${cellClasses} bg-forest-50 px-3 text-forest-900 max-md:border-0 max-md:py-2.5`}
                            >
                                <span className={mobileLabelClasses}>{translations.proposed}</span>
                                {diff.proposed ?? translations.empty_value}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            <RejectAgencyDialog
                open={rejectOpen}
                onClose={() => setRejectOpen(false)}
                actionUrl={`/admin/agencies/${change.agencyId}/changes/reject`}
                subjectName={agencyName}
                dialog={translations.dialog}
            />
        </article>
    )
}
