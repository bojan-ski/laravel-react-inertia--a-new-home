import { FormEvent, useState } from 'react'
import { router } from '@inertiajs/react'
import { Search } from 'lucide-react'
import type { AgencyDirectorySearchBarProps } from '@/types/agency'

const inputClass =
    'w-full min-w-0 appearance-none bg-transparent font-light placeholder:text-ink-faint focus-visible:outline-none [&::-webkit-search-cancel-button]:hidden'

const lineFocusClass =
    'has-[input:focus-visible]:outline-2 has-[input:focus-visible]:outline-offset-4 has-[input:focus-visible]:outline-forest'

export default function AgencyDirectorySearchBar({ filters, translations, countLabel }: AgencyDirectorySearchBarProps) {
    const [search, setSearch] = useState(filters.search ?? '')
    const [appliedSearch, setAppliedSearch] = useState(filters.search)

    if (filters.search !== appliedSearch) {
        setAppliedSearch(filters.search)
        setSearch(filters.search ?? '')
    }

    const showReset = search !== '' || Boolean(filters.search)

    function submit(event: FormEvent) {
        event.preventDefault()

        const data = search.trim() !== '' ? { search: search.trim() } : {}

        router.get('/agencies', data, { preserveState: true, replace: true })
    }

    function reset() {
        setSearch('')
        router.get('/agencies', {}, { preserveState: true, replace: true })
    }

    const resetButton = showReset && (
        <button
            type="button"
            onClick={reset}
            className="min-h-11 shrink-0 text-[13px] text-ink-muted transition-colors duration-150 hover:text-forest lg:min-h-10 lg:text-sm"
        >
            {translations.reset}
        </button>
    )

    return (
        <>
            <form
                role="search"
                onSubmit={submit}
                className="hidden items-center gap-6 border-y border-rule py-3.5 has-[input:focus-visible]:border-b-forest lg:flex"
            >
                <Search className="size-5 shrink-0" strokeWidth={1.25} aria-hidden="true" />
                <label htmlFor="agencies-search" className="sr-only">
                    {translations.label}
                </label>
                <input
                    id="agencies-search"
                    type="search"
                    maxLength={255}
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={translations.placeholder}
                    className={`min-h-10 flex-1 text-lg ${inputClass}`}
                />
                {resetButton}
                <span className="shrink-0 text-sm text-ink-muted">{countLabel}</span>
            </form>

            <form role="search" onSubmit={submit} className="mt-2 lg:hidden">
                <div className={`flex min-h-12 items-center gap-2.5 border-b border-ink ${lineFocusClass}`}>
                    <label htmlFor="agencies-search-compact" className="sr-only">
                        {translations.label}
                    </label>
                    <input
                        id="agencies-search-compact"
                        type="search"
                        maxLength={255}
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={translations.placeholder}
                        className={`text-base ${inputClass}`}
                    />
                    <button
                        type="submit"
                        aria-label={translations.submit}
                        className="flex size-11 shrink-0 items-center justify-end transition-colors duration-150 hover:text-forest"
                    >
                        <Search className="size-5" strokeWidth={1.25} aria-hidden="true" />
                    </button>
                </div>

                <div className="mt-2 flex min-h-11 items-center justify-between gap-4">
                    <span className="text-[13px] text-ink-muted">{countLabel}</span>
                    {resetButton}
                </div>
            </form>
        </>
    )
}
