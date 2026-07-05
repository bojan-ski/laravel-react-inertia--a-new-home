import { FormEvent, useState } from 'react'
import { router } from '@inertiajs/react'
import { Search } from 'lucide-react'
import type { AgencyDirectorySearchBarProps } from '@/types/agency'

export default function AgencyDirectorySearchBar({ filters, translations }: AgencyDirectorySearchBarProps) {
    const [search, setSearch] = useState(filters.search ?? '')

    function submit(event: FormEvent) {
        event.preventDefault()

        const data = search.trim() !== '' ? { search: search.trim() } : {}

        router.get('/agencies', data, { preserveState: true, replace: true })
    }

    function reset() {
        setSearch('')
        router.get('/agencies', {}, { preserveState: true, replace: true })
    }

    return (
        <div className="border-b border-border bg-surface">
            <form onSubmit={submit} className="mx-auto max-w-7xl px-6 py-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="relative sm:w-[70%]">
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-text-muted" />
                        <input
                            type="text"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={translations.placeholder}
                            className="w-full rounded-lg border border-border bg-surface py-2.5 pl-9 pr-4 text-sm text-text outline-none transition-colors focus:border-brand"
                        />
                    </div>

                    <button
                        type="submit"
                        className="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                    >
                        {translations.submit}
                    </button>

                    <button
                        type="button"
                        onClick={reset}
                        className="text-sm font-medium text-text-soft transition-colors hover:text-text"
                    >
                        {translations.reset}
                    </button>
                </div>
            </form>
        </div>
    )
}
