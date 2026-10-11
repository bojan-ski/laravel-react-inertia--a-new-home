import { Head } from '@inertiajs/react'
import AdminPageHeader from '@/Components/Admin/AdminPageHeader'
import AgencyChangeRequestRow from '@/Components/Admin/AgencyChangeRequestRow'
import AgencyModerationRow from '@/Components/Admin/AgencyModerationRow'
import EmptyState from '@/Components/UI/EmptyState'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminAgenciesPageProps } from '@/types/admin'

const sectionHeading = 'text-[26px] leading-tight font-airy tracking-[-0.02em] lg:text-[32px]'

export default function Index({ agencies, profileChanges, translations, fieldLabels }: AdminAgenciesPageProps) {

    return (
        <AdminLayout>
            <Head title={translations.title} />

            <AdminPageHeader title={translations.title} subtitle={translations.subtitle} />

            <div className="flex flex-col gap-12 lg:gap-16">
                <section>
                    <h2 className={`mb-4 ${sectionHeading}`}>{translations.verification_heading}</h2>
                    {agencies.data.length > 0 ? (
                        <ul className="border-t border-rule">
                            {agencies.data.map((agency) => (
                                <AgencyModerationRow key={agency.id} agency={agency} translations={translations} />
                            ))}
                        </ul>
                    ) : (
                        <EmptyState title={translations.empty.title} hint={translations.empty.subtitle} />
                    )}

                    <Pagination paginator={agencies} labels={translations.pagination} className="mt-8" />
                </section>

                {profileChanges.length > 0 && (
                    <section id="profile-changes" className="flex scroll-mt-8 flex-col gap-4">
                        <div className="flex flex-col gap-1.5">
                            <h2 className={sectionHeading}>{translations.changes.heading}</h2>
                            <p className="text-sm text-ink-muted">{translations.changes.subtitle}</p>
                        </div>

                        <div className="flex flex-col gap-4">
                            {profileChanges.map((change) => (
                                <AgencyChangeRequestRow
                                    key={change.agencyId}
                                    change={change}
                                    translations={translations.changes}
                                    ownerTemplate={translations.owner}
                                    fieldLabels={fieldLabels}
                                />
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </AdminLayout>
    )
}
