import { FormEvent, useState } from 'react'
import { router } from '@inertiajs/react'
import { Search, SlidersHorizontal } from 'lucide-react'
import type { ListingFilterBarProps, ListingFilters } from '@/types/listing'

const emptyFilters: ListingFilters = {
    search: '',
    listingType: '',
    propertyType: '',
    city: '',
    minPrice: '',
    maxPrice: '',
    sort: 'newest',
}

export default function ListingFilterBar({ filters, translations }: ListingFilterBarProps) {
    const [form, setForm] = useState<ListingFilters>(filters)
    const [showMore, setShowMore] = useState(
        Boolean(filters.city || filters.minPrice || filters.maxPrice)
    )

    function set<K extends keyof ListingFilters>(key: K, value: ListingFilters[K]) {
        setForm((current) => ({ ...current, [key]: value }))
    }

    function submit(event: FormEvent) {
        event.preventDefault()

        const data = Object.fromEntries(
            Object.entries(form).filter(([, value]) => value !== '')
        )

        router.get('/listings', data, { preserveState: true, preserveScroll: true, replace: true })
    }

    function reset() {
        setForm(emptyFilters)
        setShowMore(false)
        router.get('/listings', {}, { preserveState: true, preserveScroll: true, replace: true })
    }

    const segments = [
        { label: translations.all, value: '' },
        { label: translations.buy, value: 'sale' },
        { label: translations.rent, value: 'rent' },
    ]

    return (
        <form onSubmit={submit} className="border-b border-border bg-surface">
            <div className="mx-auto max-w-7xl px-6 py-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div className="relative lg:w-1/3">
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-text-muted" />
                        <input
                            type="text"
                            value={form.search}
                            onChange={(event) => set('search', event.target.value)}
                            placeholder={translations.search_placeholder}
                            className="w-full rounded-lg border border-border bg-surface py-2.5 pl-9 pr-4 text-sm text-text outline-none transition-colors focus:border-brand"
                        />
                    </div>

                    <div className="flex rounded-lg border border-border p-0.5">
                        {segments.map((segment) => (
                            <button
                                key={segment.value || 'all'}
                                type="button"
                                onClick={() => set('listingType', segment.value)}
                                className={`flex-1 rounded-md px-4 py-1.5 text-sm font-medium transition-colors lg:flex-none ${
                                    form.listingType === segment.value
                                        ? 'bg-surface-muted text-text'
                                        : 'text-text-soft hover:text-text'
                                }`}
                            >
                                {segment.label}
                            </button>
                        ))}
                    </div>

                    <select
                        value={form.propertyType}
                        onChange={(event) => set('propertyType', event.target.value)}
                        className="rounded-lg border border-border bg-surface px-3 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand"
                    >
                        <option value="">{translations.all_types}</option>
                        {Object.entries(translations.property_types).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>

                    <select
                        value={form.sort}
                        onChange={(event) => set('sort', event.target.value)}
                        className="rounded-lg border border-border bg-surface px-3 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand"
                    >
                        {Object.entries(translations.sorts).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>

                    <div className="flex items-center gap-2 lg:ml-auto">
                        <button
                            type="submit"
                            className="rounded-lg bg-surface-dark px-5 py-2.5 text-sm font-semibold text-text-inverse transition-colors hover:bg-text"
                        >
                            {translations.search}
                        </button>

                        <button
                            type="button"
                            onClick={reset}
                            className="text-sm font-medium text-text-soft transition-colors hover:text-text"
                        >
                            {translations.reset}
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    onClick={() => setShowMore((value) => !value)}
                    className="mt-3 flex items-center gap-1.5 text-sm font-medium text-text-soft transition-colors hover:text-text"
                >
                    <SlidersHorizontal className="size-4" />
                    {translations.more_filters}
                </button>

                {showMore && (
                    <div className="mt-3 flex flex-col gap-3 sm:flex-row">
                        <input
                            type="text"
                            value={form.city}
                            onChange={(event) => set('city', event.target.value)}
                            placeholder={translations.city_placeholder}
                            className="rounded-lg border border-border bg-surface px-4 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand sm:w-48"
                        />
                        <input
                            type="number"
                            min="0"
                            value={form.minPrice}
                            onChange={(event) => set('minPrice', event.target.value)}
                            placeholder={translations.min_price}
                            className="rounded-lg border border-border bg-surface px-4 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand sm:w-40"
                        />
                        <input
                            type="number"
                            min="0"
                            value={form.maxPrice}
                            onChange={(event) => set('maxPrice', event.target.value)}
                            placeholder={translations.max_price}
                            className="rounded-lg border border-border bg-surface px-4 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand sm:w-40"
                        />
                    </div>
                )}
            </div>
        </form>
    )
}
