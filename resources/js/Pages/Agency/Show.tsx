import { Head, usePage } from '@inertiajs/react'
import AccountPageHeader from '@/Components/Account/AccountPageHeader'
import AgencyListingsSection from '@/Components/Agency/AgencyListingsSection'
import AgencyProfileSection from '@/Components/Agency/AgencyProfileSection'
import AgencyStatusPanel from '@/Components/Agency/AgencyStatusPanel'
import { formatDate } from '@/Components/Listings/listingFormat'
import Button from '@/Components/UI/Button'
import FlashBanner from '@/Components/UI/FlashBanner'
import LogoTile from '@/Components/UI/LogoTile'
import PublicLayout from '@/Layouts/PublicLayout'
import type { AgencyShowPageProps } from '@/types/agency'
import type { SharedProps } from '@/types/shared'

export default function Show({ agency, pendingChange, listings, translations }: AgencyShowPageProps) {
    const { flash } = usePage<SharedProps>().props

    const settingsButton = (className: string) => (
        <Button href="/agency/settings" variant="secondary" className={className}>
            {translations.settings}
        </Button>
    )

    const editButton = (className: string) => (
        <Button href="/agency/edit" variant="secondary" className={className}>
            {translations.edit}
        </Button>
    )

    return (
        <PublicLayout>
            <Head title={agency.name} />

            <AccountPageHeader
                title={translations.title}
                subtitle={translations.subtitle}
                leading={<LogoTile name={agency.name} size="lg" />}
                action={
                    <div className="flex gap-2.5">
                        {settingsButton('min-h-12')}
                        {editButton('min-h-12')}
                    </div>
                }
                mobileAction={
                    <div className="mt-2 grid grid-cols-2 gap-2">
                        {editButton('min-h-11 w-full')}
                        {settingsButton('min-h-11 w-full')}
                    </div>
                }
            />

            <div className="flex flex-col gap-3 px-5 pt-6 md:px-10 lg:pt-10 xl:px-16">
                {flash.success && <FlashBanner message={flash.success} variant="success" className="" />}
                {flash.error && <FlashBanner message={flash.error} variant="error" className="" />}

                <AgencyStatusPanel agency={agency} translations={translations} />

                {pendingChange && (
                    <FlashBanner
                        message={translations.pending_changes.replace(
                            ':date',
                            formatDate(pendingChange.submittedAt, 'long'),
                        )}
                        variant="info"
                        className=""
                    />
                )}
            </div>

            <div className="flex flex-col gap-8 px-5 pt-8 pb-10 md:px-10 lg:pt-16 lg:pb-24 xl:grid xl:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] xl:gap-20 xl:px-16">
                <div className="min-w-0 xl:col-start-2 xl:row-start-1">
                    <AgencyListingsSection
                        listings={listings}
                        translations={translations.listings}
                        agencyStatus={agency.status}
                    />
                </div>

                <div className="min-w-0 xl:col-start-1 xl:row-start-1">
                    <AgencyProfileSection agency={agency} translations={translations.profile} />
                </div>
            </div>
        </PublicLayout>
    )
}
