import { FormEvent, useState } from 'react'
import { router } from '@inertiajs/react'
import { ChevronDown, Search } from 'lucide-react'
import Button from '@/Components/UI/Button'
import type { AdminUserFilterBarProps, AdminUserFilterForm, AdminUserFilterSelectProps } from '@/types/admin'

const emptyForm: AdminUserFilterForm = { role: '', status: '', search: '' }

function FilterSelect({ id, label, value, onChange, allLabel, options }: AdminUserFilterSelectProps) {
    return (
        <div className="relative flex min-h-11 items-center border-b border-ink text-[15px] transition-colors duration-150 focus-within:border-forest xl:min-w-42.5">
            <label htmlFor={id} className="sr-only">
                {label}
            </label>
            <select
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="min-h-11 w-full min-w-0 cursor-pointer appearance-none bg-transparent pr-7"
            >
                <option value="">{allLabel}</option>
                {Object.entries(options).map(([optionValue, optionLabel]) => (
                    <option key={optionValue} value={optionValue}>
                        {optionLabel}
                    </option>
                ))}
            </select>
            <ChevronDown
                className="pointer-events-none absolute right-0 size-4"
                strokeWidth={1.25}
                aria-hidden="true"
            />
        </div>
    )
}

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
        <form role="search" onSubmit={submit} className="flex flex-col gap-4 xl:flex-row xl:items-end xl:gap-6">
            <div className="flex min-h-11 items-center gap-3 border-b border-ink transition-colors duration-150 focus-within:border-forest xl:flex-1">
                <Search className="size-4.5 shrink-0" strokeWidth={1.25} aria-hidden="true" />
                <label htmlFor="admin-users-search" className="sr-only">
                    {translations.filters.search_label}
                </label>
                <input
                    id="admin-users-search"
                    type="search"
                    maxLength={255}
                    value={form.search}
                    onChange={(event) => set('search', event.target.value)}
                    placeholder={translations.search_placeholder}
                    className="min-h-11 w-full min-w-0 bg-transparent text-base font-light outline-none placeholder:text-ink-muted"
                />
            </div>

            <div className="grid grid-cols-2 gap-4 xl:flex xl:gap-6">
                <FilterSelect
                    id="admin-users-role"
                    label={translations.filters.role_label}
                    value={form.role}
                    onChange={(value) => set('role', value)}
                    allLabel={translations.filters.all_roles}
                    options={translations.roles}
                />
                <FilterSelect
                    id="admin-users-status"
                    label={translations.filters.status_label}
                    value={form.status}
                    onChange={(value) => set('status', value)}
                    allLabel={translations.filters.all_statuses}
                    options={translations.statuses}
                />
            </div>

            <div className="flex items-center gap-6 xl:items-end">
                <Button type="submit" size="sm">
                    {translations.filters.search}
                </Button>
                <Button variant="link" onClick={reset} className="min-h-11 xl:min-h-0 xl:pb-3">
                    {translations.filters.reset}
                </Button>
            </div>
        </form>
    )
}
