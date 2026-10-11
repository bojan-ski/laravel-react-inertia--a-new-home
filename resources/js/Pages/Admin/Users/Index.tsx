import { Head } from '@inertiajs/react'
import AdminPageHeader from '@/Components/Admin/AdminPageHeader'
import AdminUserFilterBar from '@/Components/Admin/AdminUserFilterBar'
import AdminUserRow from '@/Components/Admin/AdminUserRow'
import { countLabel } from '@/Components/Listings/listingFormat'
import Button from '@/Components/UI/Button'
import EmptyState from '@/Components/UI/EmptyState'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminUsersPageProps } from '@/types/admin'

const headingClasses = 'py-2.5 pr-6 text-left text-xs font-normal tracking-widest text-ink-muted uppercase'

export default function Index({ users, filters, translations }: AdminUsersPageProps) {
    const hasFilters = Boolean(filters.role || filters.status || filters.search)
    const { columns } = translations

    return (
        <AdminLayout>
            <Head title={translations.title} />

            <AdminPageHeader title={translations.title} subtitle={translations.subtitle} />

            <AdminUserFilterBar filters={filters} translations={translations} />

            <div>
                {users.data.length > 0 ? (
                    <>
                        <p className="mb-3 text-sm text-ink-muted">
                            {countLabel(users.total, translations.count_one, translations.count)}
                        </p>

                        <table className="w-full max-xl:block xl:table-fixed">
                            <colgroup className="max-xl:hidden">
                                <col />
                                <col className="w-37.5" />
                                <col className="w-32.5" />
                                <col className="w-[28%]" />
                                <col className="w-50" />
                            </colgroup>
                            <thead className="max-xl:sr-only">
                                <tr className="border-b border-ink">
                                    <th scope="col" className={headingClasses}>
                                        {columns.user}
                                    </th>
                                    <th scope="col" className={headingClasses}>
                                        {columns.role}
                                    </th>
                                    <th scope="col" className={headingClasses}>
                                        {columns.status}
                                    </th>
                                    <th scope="col" className={headingClasses}>
                                        {columns.details}
                                    </th>
                                    <th scope="col" className="py-2.5">
                                        <span className="sr-only">{columns.actions}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="max-xl:block max-xl:border-t max-xl:border-rule">
                                {users.data.map((user) => (
                                    <AdminUserRow key={user.id} user={user} translations={translations} />
                                ))}
                            </tbody>
                        </table>
                    </>
                ) : (
                    <EmptyState
                        title={translations.empty.title}
                        hint={hasFilters ? translations.empty.filtered : undefined}
                        action={
                            hasFilters ? (
                                <Button variant="strong-link" href="/admin/users" className="text-sm">
                                    {translations.empty.reset}
                                </Button>
                            ) : undefined
                        }
                    />
                )}

                <Pagination paginator={users} labels={translations.pagination} className="mt-8" />
            </div>
        </AdminLayout>
    )
}
