import type { AgencyRegistrationDetails, AgencyRegistrationLabels } from '@/types/agency'

export function formatRegistration(template: string, number: string | null, country: string | null): string | null {
    if (!number || !country) {
        return null
    }

    return template.replace(':number', number).replace(':country', country)
}

export function formatMonthYear(iso: string): string {
    return new Date(iso).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' })
}

export function websiteHost(url: string): string {
    return url
        .trim()
        .replace(/^[a-z][a-z0-9+.-]*:\/\//i, '')
        .replace(/^www\./i, '')
        .replace(/\/+$/, '')
}

export function descriptionParagraphs(description: string | null): string[] {
    if (!description) {
        return []
    }

    return description
        .split(/\n\s*\n/)
        .map((paragraph) => paragraph.trim())
        .filter((paragraph) => paragraph !== '')
}

export function agencyRegistrationLines(agency: AgencyRegistrationDetails, labels: AgencyRegistrationLabels): string[] {
    return [
        formatRegistration(labels.registration, agency.registrationNumber, agency.registrationCountry),
        agency.brokerRegisterNumber ? labels.broker_register.replace(':number', agency.brokerRegisterNumber) : null,
        agency.vatId ? labels.vat_id.replace(':number', agency.vatId) : null,
    ].filter((line): line is string => line !== null)
}
