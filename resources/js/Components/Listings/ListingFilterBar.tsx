import { useCallback, useState, type FormEvent } from 'react'
import { router } from '@inertiajs/react'
import { Search } from 'lucide-react'
import { FilterActions, PriceFields } from '@/Components/Listings/ListingFilterFields'
import ListingFiltersSheet from '@/Components/Listings/ListingFiltersSheet'
import ListingMoreFilters from '@/Components/Listings/ListingMoreFilters'
import ListingTextSelect from '@/Components/Listings/ListingTextSelect'
import {
    BEDROOM_OPTIONS,
    emptyFilters,
    moreFiltersCount,
    nonEmptyFilters,
    sheetFiltersCount,
} from '@/Components/Listings/listingFilters'
import Popover from '@/Components/UI/Popover'
import SegmentedToggle from '@/Components/UI/SegmentedToggle'
import type { ListingFilterBarProps, ListingFilters } from '@/types/listing'

type OpenPopover = 'price' | 'more' | null

const searchInputClass =
    'w-full min-w-0 appearance-none bg-transparent placeholder:text-ink-faint [&::-webkit-search-cancel-button]:hidden'

export default function ListingFilterBar({ filters, translations, amenityLabels, actionsSlot }: ListingFilterBarProps) {
    const [form, setForm] = useState<ListingFilters>(filters)
    const [openPopover, setOpenPopover] = useState<OpenPopover>(null)
    const [sheetOpen, setSheetOpen] = useState(false)

    const setPriceOpen = useCallback((open: boolean) => setOpenPopover(open ? 'price' : null), [])
    const setMoreOpen = useCallback((open: boolean) => setOpenPopover(open ? 'more' : null), [])
    const closeSheet = useCallback(() => setSheetOpen(false), [])

    function set<K extends keyof ListingFilters>(key: K, value: ListingFilters[K]) {
        setForm((current) => ({ ...current, [key]: value }))
    }

    function apply(next: ListingFilters) {
        setForm(next)
        setOpenPopover(null)
        setSheetOpen(false)
        router.get('/listings', nonEmptyFilters(next), { preserveState: true, preserveScroll: true, replace: true })
    }

    function update<K extends keyof ListingFilters>(key: K, value: ListingFilters[K]) {
        apply({ ...form, [key]: value })
    }

    function submit(event: FormEvent) {
        event.preventDefault()
        apply(form)
    }

    function clearAll() {
        setForm(emptyFilters)
        setOpenPopover(null)
        setSheetOpen(false)
        router.get('/listings', {}, { preserveState: true, preserveScroll: true, replace: true })
    }

    const listingTypeOptions = [
        { value: '', label: translations.all },
        { value: 'sale', label: translations.buy },
        { value: 'rent', label: translations.rent },
    ]
    const propertyTypeOptions = [{ value: '', label: translations.all_types }].concat(
        Object.entries(translations.property_types).map(([value, label]) => ({ value, label })),
    )
    const bedroomOptions = [{ value: '', label: translations.beds }].concat(
        BEDROOM_OPTIONS.map((count) => ({
            value: String(count),
            label: translations.beds_option.replace(':count', String(count)),
        })),
    )
    const toOptions = (labels: Record<string, string>) =>
        Object.entries(labels).map(([value, label]) => ({ value, label }))

    const moreCount = moreFiltersCount(filters)
    const sheetCount = sheetFiltersCount(filters)
    const fieldProps = { form, set, translations, idPrefix: 'bar' }

    return (
        <form role="search" onSubmit={submit}>
            <div className="hidden items-center gap-8 border-y border-rule py-2 text-[15px] xl:flex 2xl:gap-10">
                <SegmentedToggle
                    name="listingType"
                    legend={translations.listing_type_label}
                    options={listingTypeOptions}
                    value={form.listingType}
                    onChange={(value) => update('listingType', value)}
                    size="bar"
                    className="shrink-0"
                />

                <div className="min-w-40 flex-1">
                    <label htmlFor="listings-search" className="sr-only">
                        {translations.search_label}
                    </label>
                    <input
                        id="listings-search"
                        type="search"
                        maxLength={255}
                        value={form.search}
                        onChange={(event) => set('search', event.target.value)}
                        placeholder={translations.search_placeholder}
                        className={`min-h-11 ${searchInputClass}`}
                    />
                </div>

                <ListingTextSelect
                    id="listings-property-type"
                    label={translations.property_type_label}
                    value={form.propertyType}
                    onChange={(value) => update('propertyType', value)}
                    options={propertyTypeOptions}
                    active={form.propertyType !== ''}
                />

                <Popover
                    id="listings-price-filters"
                    label={translations.price}
                    open={openPopover === 'price'}
                    onOpenChange={setPriceOpen}
                    active={Boolean(filters.minPrice || filters.maxPrice)}
                >
                    <PriceFields {...fieldProps} />
                    <FilterActions
                        clearLabel={translations.clear}
                        onClear={() => apply({ ...form, minPrice: '', maxPrice: '' })}
                        applyLabel={translations.apply}
                    />
                </Popover>

                <ListingTextSelect
                    id="listings-beds"
                    label={translations.beds}
                    value={form.minBedrooms}
                    onChange={(value) => update('minBedrooms', value)}
                    options={bedroomOptions}
                    active={form.minBedrooms !== ''}
                />

                <Popover
                    id="listings-more-filters"
                    label={moreCount > 0 ? `${translations.more_filters} · ${moreCount}` : translations.more_filters}
                    open={openPopover === 'more'}
                    onOpenChange={setMoreOpen}
                    active={moreCount > 0}
                    align="end"
                    width="md"
                    chevron={false}
                >
                    <ListingMoreFilters {...fieldProps} amenityLabels={amenityLabels} />
                    <FilterActions
                        clearLabel={translations.clear_all}
                        onClear={clearAll}
                        applyLabel={translations.apply}
                    />
                </Popover>

                <ListingTextSelect
                    id="listings-sort"
                    label={translations.sort_label}
                    value={form.sort}
                    onChange={(value) => update('sort', value)}
                    options={toOptions(translations.sorts)}
                    className="text-ink-muted"
                />

                {actionsSlot}
            </div>

            <div className="xl:hidden">
                <div className="flex min-h-12 items-center border-b border-ink has-[input:focus-visible]:border-forest has-[input:focus-visible]:outline-2 has-[input:focus-visible]:outline-offset-4 has-[input:focus-visible]:outline-forest">
                    <label htmlFor="listings-search-compact" className="sr-only">
                        {translations.search_label}
                    </label>
                    <input
                        id="listings-search-compact"
                        type="search"
                        maxLength={255}
                        value={form.search}
                        onChange={(event) => set('search', event.target.value)}
                        placeholder={translations.search_placeholder}
                        className={`text-base font-light focus-visible:outline-none ${searchInputClass}`}
                    />
                    <button
                        type="submit"
                        aria-label={translations.search}
                        className="flex size-11 shrink-0 items-center justify-end transition-colors duration-150 hover:text-forest"
                    >
                        <Search className="size-5" strokeWidth={1.25} aria-hidden="true" />
                    </button>
                </div>

                <div className="flex items-center justify-between gap-4 border-b border-rule text-[15px]">
                    <SegmentedToggle
                        name="listingTypeCompact"
                        legend={translations.listing_type_label}
                        options={listingTypeOptions}
                        value={form.listingType}
                        onChange={(value) => update('listingType', value)}
                        size="bar"
                    />

                    <div className="flex items-center gap-4.5">
                        <button
                            type="button"
                            onClick={() => setSheetOpen(true)}
                            className={`min-h-11 transition-colors duration-150 hover:text-forest ${
                                sheetCount > 0 ? 'text-forest' : ''
                            }`}
                        >
                            {sheetCount > 0
                                ? translations.filters_count.replace(':count', String(sheetCount))
                                : translations.filters}
                        </button>

                        <ListingTextSelect
                            id="listings-sort-compact"
                            label={translations.sort_label}
                            value={form.sort}
                            onChange={(value) => update('sort', value)}
                            options={toOptions(translations.sorts_short)}
                            className="text-ink-muted"
                        />
                    </div>
                </div>

                {actionsSlot && <div className="flex justify-end">{actionsSlot}</div>}
            </div>

            {sheetOpen && (
                <ListingFiltersSheet
                    open={sheetOpen}
                    onClose={closeSheet}
                    filters={form}
                    translations={translations}
                    amenityLabels={amenityLabels}
                    onApply={apply}
                    onClearAll={clearAll}
                />
            )}
        </form>
    )
}
