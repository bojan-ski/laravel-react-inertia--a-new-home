export function formatLicense(template: string, licenseNumber: string | null, licenseState: string | null): string | null {
    if (!licenseNumber || !licenseState) {
        return null
    }

    return template.replace(':number', licenseNumber).replace(':state', licenseState)
}
