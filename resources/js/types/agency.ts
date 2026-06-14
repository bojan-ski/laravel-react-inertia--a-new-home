export interface AgencySetupSections {
    identity: string
    contact: string
}

export interface AgencySetupFields {
    name: string
    description: string
    email: string
    email_note: string
    phone: string
    website: string
    address: string
}

export interface AgencySetupTranslations {
    title: string
    subtitle: string
    helper: string
    submit: string
    back: string
    submit_note: string
    flash: string
    sections: AgencySetupSections
    fields: AgencySetupFields
}

export interface AgencySetupPageProps {
    translations: AgencySetupTranslations
}

export interface AgencySetupFormData {
    name: string
    email: string
    phone: string
    website: string
    address: string
    description: string
}

export interface AgencyDashboardData {
    id: number
    name: string
    slug: string
    email: string
    phone: string | null
    website: string | null
    address: string | null
    description: string | null
    ownerName: string | null
    ownerEmail: string | null
    createdAt: string | null
}

export interface AgencyDashboardTranslations {
    title: string
    subtitle: string
    pending_notice: string
}

export interface AgencyDashboardPageProps {
    agency: AgencyDashboardData
    translations: AgencyDashboardTranslations
}
