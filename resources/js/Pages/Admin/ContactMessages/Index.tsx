import { Head, Link, usePage } from '@inertiajs/react'
import { Inbox } from 'lucide-react'
import ContactMessageRow from '@/Components/Admin/ContactMessageRow'
import FlashBanner from '@/Components/UI/FlashBanner'
import Pagination from '@/Components/UI/Pagination'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminContactMessagesPageProps, ContactMessageStatus } from '@/types/contact'
import type { SharedProps } from '@/types/shared'

const STATUSES: ContactMessageStatus[] = ['new', 'handled']

export default function Index({ messages, status, translations }: AdminContactMessagesPageProps) {
    const { flash } = usePage<SharedProps>().props
    const empty = translations.empty[status]

    return (
        <AdminLayout>
            <Head title={translations.title} />

            {flash.success && <FlashBanner message={flash.success} variant="success" />}
            {flash.error && <FlashBanner message={flash.error} variant="error" />}

            <header>
                <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">{translations.title}</h1>
                <p className="mt-2 text-text-soft">{translations.subtitle}</p>
            </header>

            <div className="mt-8 inline-flex rounded-lg border border-border bg-surface p-1">
                {STATUSES.map((tab) => (
                    <Link
                        key={tab}
                        href={`/admin/contact-messages?status=${tab}`}
                        preserveScroll
                        className={`rounded-md px-4 py-1.5 text-sm font-medium transition-colors ${
                            tab === status ? 'bg-surface-dark text-text-inverse' : 'text-text-soft hover:text-text'
                        }`}
                    >
                        {translations.tabs[tab]}
                    </Link>
                ))}
            </div>

            {messages.data.length > 0 ? (
                <div className="mt-6 flex flex-col gap-4">
                    {messages.data.map((message) => (
                        <ContactMessageRow key={message.id} message={message} translations={translations} />
                    ))}
                </div>
            ) : (
                <div className="mt-10 flex flex-col items-center gap-3 py-20 text-center">
                    <Inbox className="size-10 text-text-muted" />
                    <p className="font-display text-2xl font-semibold text-text">{empty.title}</p>
                    <p className="text-text-soft">{empty.subtitle}</p>
                </div>
            )}

            <Pagination paginator={messages} labels={translations.pagination} />
        </AdminLayout>
    )
}
