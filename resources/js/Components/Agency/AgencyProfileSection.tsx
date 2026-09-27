import type { ReactNode } from 'react'
import { formatLicense } from '@/Components/Agencies/agencyFormat'
import type { AgencyProfileSectionProps } from '@/types/agency'

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex flex-col gap-1 border-t border-border py-4 first:border-t-0 first:pt-0 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
            <dt className="text-sm text-text-muted sm:w-40 sm:shrink-0">{label}</dt>
            <dd className="text-text sm:flex-1 sm:text-right">{children}</dd>
        </div>
    )
}

export default function AgencyProfileSection({ agency, translations }: AgencyProfileSectionProps) {
    return (
        <section className="rounded-xl border border-border bg-surface p-6 sm:p-8">
            <h2 className="font-display text-xl font-semibold text-text">{translations.heading}</h2>
            <p className="mt-1 text-sm text-text-soft">{translations.note}</p>

            <dl className="mt-6">
                <Row label={translations.name}>{agency.name}</Row>

                {agency.description && <Row label={translations.description}>{agency.description}</Row>}

                <Row label={translations.email}>{agency.email}</Row>

                {agency.phone && <Row label={translations.phone}>{agency.phone}</Row>}

                {agency.website && (
                    <Row label={translations.website}>
                        <a
                            href={agency.website}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-brand transition-colors hover:text-brand-dark"
                        >
                            {agency.website}
                        </a>
                    </Row>
                )}

                {agency.address && <Row label={translations.address}>{agency.address}</Row>}

                <Row label={translations.legal_name}>{agency.legalName ?? translations.missing}</Row>

                <Row label={translations.ein}>{agency.ein ?? translations.missing}</Row>

                <Row label={translations.license}>
                    {formatLicense(translations.license_value, agency.licenseNumber, agency.licenseState) ??
                        translations.missing}
                </Row>
            </dl>
        </section>
    )
}
