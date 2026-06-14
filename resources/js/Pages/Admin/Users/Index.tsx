import { Head, Link } from '@inertiajs/react'
import AdminUserFilterBar from '@/Components/Admin/AdminUserFilterBar'
import AdminUserRow from '@/Components/Admin/AdminUserRow'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminUsersPageProps } from '@/types/admin'

export default function Index({ users, filters, translations }: AdminUsersPageProps) {
    const hasFilters = Boolean(filters.role || filters.status || filters.search)

    return (
        <AdminLayout>
            <Head title={translations.title} />

            <header>
                <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                    {translations.title}
                </h1>
                <p className="mt-2 text-text-soft">{translations.subtitle}</p>
            </header>

            <div className="mt-8">
                <AdminUserFilterBar filters={filters} translations={translations} />
            </div>

            <p className="mt-4 text-sm text-text-muted">
                {translations.count.replace(':count', String(users.total))}
            </p>

            {users.data.length > 0 ? (
                <div className="mt-4 flex flex-col gap-4">
                    {users.data.map((user) => (
                        <AdminUserRow key={user.id} user={user} translations={translations} />
                    ))}
                </div>
            ) : (
                <div className="mt-10 flex flex-col items-center gap-3 py-16 text-center">
                    <p className="text-lg font-medium text-text-soft">{translations.empty.title}</p>
                    {hasFilters && (
                        <>
                            <p className="text-sm text-text-muted">{translations.empty.filtered}</p>
                            <Link
                                href="/admin/users"
                                className="text-sm font-medium text-brand transition-colors hover:text-brand-dark"
                            >
                                {translations.empty.reset}
                            </Link>
                        </>
                    )}
                </div>
            )}

            <Pagination paginator={users} labels={translations.pagination} />
        </AdminLayout>
    )
}
