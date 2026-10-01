import { Head, Link, usePage } from '@inertiajs/react'
import AdminPageHeader from '@/Components/Admin/AdminPageHeader'
import ContactMessageRow from '@/Components/Admin/ContactMessageRow'
import EmptyState from '@/Components/UI/EmptyState'
import FlashBanner from '@/Components/UI/FlashBanner'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminContactMessagesPageProps, ContactMessageStatus } from '@/types/contact'
import type { SharedProps } from '@/types/shared'

const STATUSES: ContactMessageStatus[] = ['new', 'handled']

export default function Index({ messages, status, translations }: AdminContactMessagesPageProps) {
    const { flash, adminCounts } = usePage<SharedProps>().props
    const empty = translations.empty[status]
    const newCount = adminCounts?.newContactMessages ?? 0
    const { tabs } = translations

    function tabLabel(tab: ContactMessageStatus): string {
        if (tab === 'new' && newCount > 0) {
            return tabs.new_count.replace(':label', tabs.new).replace(':count', newCount.toLocaleString('en-GB'))
        }

        return tabs[tab]
    }

    return (
        <AdminLayout>
            <Head title={translations.title} />

            <AdminPageHeader title={translations.title} subtitle={translations.subtitle} />

            {flash.success && <FlashBanner message={flash.success} variant="success" className="" />}
            {flash.error && <FlashBanner message={flash.error} variant="error" className="" />}

            <nav aria-label={tabs.aria_label}>
                <ul className="flex gap-8 border-b border-rule text-sm md:text-[15px]">
                    {STATUSES.map((tab) => {
                        const active = tab === status

                        return (
                            <li key={tab}>
                                <Link
                                    href={`/admin/contact-messages?status=${tab}`}
                                    preserveScroll
                                    aria-current={active ? 'page' : undefined}
                                    className={`-mb-px block pt-3.5 pb-3.25 transition-colors duration-150 focus-visible:-outline-offset-2 ${
                                        active ? 'border-b border-forest text-forest' : 'text-ink-muted hover:text-forest'
                                    }`}
                                >
                                    {tabLabel(tab)}
                                </Link>
                            </li>
                        )
                    })}
                </ul>
            </nav>

            <div>
                {messages.data.length > 0 ? (
                    <ul>
                        {messages.data.map((message) => (
                            <ContactMessageRow key={message.id} message={message} translations={translations} />
                        ))}
                    </ul>
                ) : (
                    <EmptyState title={empty.title} hint={empty.subtitle} />
                )}

                <Pagination paginator={messages} labels={translations.pagination} className="mt-8" />
            </div>
        </AdminLayout>
    )
}
