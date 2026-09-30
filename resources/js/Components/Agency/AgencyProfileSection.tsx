import type { ReactNode } from 'react'
import { formatRegistration, websiteHost } from '@/Components/Agencies/agencyFormat'
import type { AgencyProfileSectionProps } from '@/types/agency'

export interface AgencyDetailRowProps {
    label: string
    children: ReactNode
    multiline?: boolean
}

function Row({ label, children, multiline = false }: AgencyDetailRowProps) {
    return (
        <div className="flex justify-between gap-4 border-b border-rule py-3 text-sm lg:grid lg:grid-cols-[200px_minmax(0,1fr)] lg:gap-6 lg:py-3.5 lg:text-[15px]">
            <dt className="shrink-0 text-ink-muted">{label}</dt>
            <dd className={`min-w-0 text-right wrap-break-word lg:text-left ${multiline ? 'whitespace-pre-line' : ''}`}>
                {children}
            </dd>
        </div>
    )
}

export default function AgencyProfileSection({ agency, translations }: AgencyProfileSectionProps) {
    const notSet = <span className="text-ink-muted">{translations.not_set}</span>
    const missing = <span className="text-ink-muted">{translations.missing}</span>
    const registration = formatRegistration(
        translations.registration_value,
        agency.registrationNumber,
        agency.registrationCountry,
    )

    return (
        <section aria-labelledby="agency-details-heading">
            <h2
                id="agency-details-heading"
                className="mb-3 text-[30px] leading-tight font-airy tracking-[-0.02em] lg:mb-2 lg:text-[40px] lg:tracking-[-0.03em]"
            >
                {translations.heading}
            </h2>
            <p className="mb-4 hidden text-sm text-ink-muted lg:block">{translations.note}</p>

            <dl className="lg:border-t lg:border-rule">
                <Row label={translations.name}>{agency.name}</Row>
                <Row label={translations.description} multiline>
                    {agency.description ?? notSet}
                </Row>
                <Row label={translations.email}>{agency.email}</Row>
                <Row label={translations.phone}>{agency.phone ?? notSet}</Row>
                <Row label={translations.website}>
                    {agency.website ? (
                        <a
                            href={agency.website}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="transition-colors duration-150 hover:text-forest"
                        >
                            {websiteHost(agency.website)}
                        </a>
                    ) : (
                        notSet
                    )}
                </Row>
                <Row label={translations.address} multiline>
                    {agency.address ?? notSet}
                </Row>
                <Row label={translations.legal_name}>{agency.legalName ?? missing}</Row>
                <Row label={translations.registration}>{registration ?? missing}</Row>
                <Row label={translations.vat_id}>{agency.vatId ?? notSet}</Row>
                <Row label={translations.broker_register_number}>{agency.brokerRegisterNumber ?? notSet}</Row>
            </dl>
        </section>
    )
}
