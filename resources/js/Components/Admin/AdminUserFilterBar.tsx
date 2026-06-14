import { FormEvent, useState } from 'react'
import { router } from '@inertiajs/react'
import { Search } from 'lucide-react'
import type { AdminUserFilterBarProps, AdminUserFilterForm } from '@/types/admin'

const emptyForm: AdminUserFilterForm = { role: '', status: '', search: '' }

export default function AdminUserFilterBar({ filters, translations }: AdminUserFilterBarProps) {
    const [form, setForm] = useState<AdminUserFilterForm>({
        role: filters.role ?? '',
        status: filters.status ?? '',
        search: filters.search ?? '',
    })

    function set<K extends keyof AdminUserFilterForm>(key: K, value: string) {
        setForm((current) => ({ ...current, [key]: value }))
    }

    function submit(event: FormEvent) {
        event.preventDefault()

        const data = Object.fromEntries(Object.entries(form).filter(([, value]) => value !== ''))

        router.get('/admin/users', data, { preserveState: true, preserveScroll: true, replace: true })
    }

    function reset() {
        setForm(emptyForm)
        router.get('/admin/users', {}, { preserveState: true, preserveScroll: true, replace: true })
    }

    return (
        <form onSubmit={submit} className="rounded-2xl border border-border bg-surface p-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div className="relative sm:flex-1">
                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-text-muted" />
                    <input
                        type="text"
                        value={form.search}
                        onChange={(event) => set('search', event.target.value)}
                        placeholder={translations.search_placeholder}
                        className="w-full rounded-lg border border-border bg-surface py-2.5 pl-9 pr-4 text-sm text-text outline-none transition-colors focus:border-brand"
                    />
                </div>

                <select
                    value={form.role}
                    onChange={(event) => set('role', event.target.value)}
                    className="rounded-lg border border-border bg-surface px-3 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand"
                >
                    <option value="">{translations.filters.all_roles}</option>
                    {Object.entries(translations.roles).map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </select>

                <select
                    value={form.status}
                    onChange={(event) => set('status', event.target.value)}
                    className="rounded-lg border border-border bg-surface px-3 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand"
                >
                    <option value="">{translations.filters.all_statuses}</option>
                    {Object.entries(translations.statuses).map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </select>

                <div className="flex items-center gap-2">
                    <button
                        type="submit"
                        className="rounded-lg bg-surface-dark px-5 py-2.5 text-sm font-semibold text-text-inverse transition-colors hover:bg-text"
                    >
                        {translations.filters.search}
                    </button>
                    <button
                        type="button"
                        onClick={reset}
                        className="text-sm font-medium text-text-soft transition-colors hover:text-text"
                    >
                        {translations.filters.reset}
                    </button>
                </div>
            </div>
        </form>
    )
}
