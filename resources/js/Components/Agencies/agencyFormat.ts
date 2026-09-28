import type { AgencyRegistrationDetails, AgencyRegistrationLabels } from '@/types/agency'

export function formatRegistration(template: string, number: string | null, country: string | null): string | null {
    if (!number || !country) {
        return null
    }

    return template.replace(':number', number).replace(':country', country)
}

export function agencyRegistrationLines(agency: AgencyRegistrationDetails, labels: AgencyRegistrationLabels): string[] {
    return [
        formatRegistration(labels.registration, agency.registrationNumber, agency.registrationCountry),
        agency.brokerRegisterNumber ? labels.broker_register.replace(':number', agency.brokerRegisterNumber) : null,
        agency.vatId ? labels.vat_id.replace(':number', agency.vatId) : null,
    ].filter((line): line is string => line !== null)
}
