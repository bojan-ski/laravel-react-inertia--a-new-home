import { useState } from 'react'
import { router } from '@inertiajs/react'
import RejectAgencyDialog from '@/Components/Admin/RejectAgencyDialog'
import { formatRegistration, websiteHost } from '@/Components/Agencies/agencyFormat'
import { formatDate } from '@/Components/Listings/listingFormat'
import Button from '@/Components/UI/Button'
import LogoTile from '@/Components/UI/LogoTile'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { AdminAgencyModerationRowProps } from '@/types/admin'

export default function AgencyModerationRow({ agency, translations }: AdminAgencyModerationRowProps) {
    const [rejectOpen, setRejectOpen] = useState(false)
    const [processing, setProcessing] = useState(false)
    const actionLabel = useActionLabel()

    function approve() {
        router.post(`/admin/agencies/${agency.id}/approve`, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    const notProvided = translations.not_provided
    const owner = translations.owner
        .replace(':name', agency.ownerName ?? '')
        .replace(':email', agency.ownerEmail ?? '')
    const contact = translations.contact.replace(
        ':contact',
        [agency.email, agency.phone].filter(Boolean).join(' • ') || '—',
    )
    const legal = translations.legal
        .replace(':name', agency.legalName ?? notProvided)
        .replace(':vat', agency.vatId ?? notProvided)
    const registration = translations.registration.replace(
        ':registration',
        formatRegistration(translations.registration_value, agency.registrationNumber, agency.registrationCountry) ??
            notProvided,
    )
    const broker = translations.broker_register.replace(':number', agency.brokerRegisterNumber ?? notProvided)
    const [websitePrefix] = translations.website.split(':website')

    return (
        <li className="grid grid-cols-[56px_minmax(0,1fr)] items-center gap-x-3.5 gap-y-1.25 border-b border-rule py-6 transition-colors duration-150 hover:bg-sand/55 md:grid-cols-[72px_minmax(0,1fr)_auto] md:items-start md:gap-x-7">
            <LogoTile name={agency.name} size="queue" />

            <div className="contents md:flex md:min-w-0 md:flex-col md:gap-1.25">
                <p className="text-xl leading-tight font-light tracking-[-0.015em] wrap-break-word md:text-2xl">
                    {agency.name}
                </p>

                <div className="col-span-2 mt-2.5 flex flex-col gap-1.25 md:mt-0">
                    {agency.address && <p className="text-sm wrap-break-word">{agency.address}</p>}

                    <div className="flex flex-col gap-1.25 text-[13px] wrap-break-word text-ink-muted">
                        <p>{owner}</p>
                        <p>{contact}</p>
                        <p>{legal}</p>
                        <p>{registration}</p>
                        <p>{broker}</p>
                        {agency.website && (
                            <p>
                                {websitePrefix}
                                <a
                                    href={agency.website}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="border-b border-current text-ink transition-colors duration-150 hover:text-forest"
                                >
                                    {websiteHost(agency.website)}
                                </a>
                            </p>
                        )}
                        {agency.description && (
                            <p className="line-clamp-2 whitespace-pre-line">{agency.description}</p>
                        )}
                        <p>{translations.submitted.replace(':date', formatDate(agency.createdAt))}</p>
                    </div>
                </div>
            </div>

            <div className="col-span-2 mt-2.5 flex items-start gap-2.5 md:col-span-1 md:mt-0">
                <Button
                    variant="secondary"
                    size="sm"
                    onClick={() => setRejectOpen(true)}
                    disabled={processing}
                    aria-label={actionLabel(translations.actions.reject, agency.name)}
                >
                    {translations.actions.reject}
                </Button>
                <Button
                    size="sm"
                    onClick={approve}
                    disabled={processing}
                    aria-label={actionLabel(translations.actions.approve, agency.name)}
                >
                    {translations.actions.approve}
                </Button>
            </div>

            <RejectAgencyDialog
                open={rejectOpen}
                onClose={() => setRejectOpen(false)}
                actionUrl={`/admin/agencies/${agency.id}/reject`}
                subjectName={agency.name}
                dialog={translations.dialog}
            />
        </li>
    )
}
