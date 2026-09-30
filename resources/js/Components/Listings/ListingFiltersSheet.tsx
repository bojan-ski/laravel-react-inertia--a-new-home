import { useId, useState, type FormEvent } from 'react'
import {
    AmenityChips,
    AreaFields,
    BathroomsSelect,
    BedroomsSelect,
    CityField,
    FilterActions,
    PriceFields,
    PropertyTypeChips,
} from '@/Components/Listings/ListingFilterFields'
import Dialog from '@/Components/UI/Dialog'
import type { ListingFilters, ListingFiltersSheetProps } from '@/types/listing'

export default function ListingFiltersSheet({
    open,
    onClose,
    filters,
    translations,
    amenityLabels,
    onApply,
    onClearAll,
}: ListingFiltersSheetProps) {
    const formId = useId()
    const [draft, setDraft] = useState<ListingFilters>(filters)

    function set<K extends keyof ListingFilters>(key: K, value: ListingFilters[K]) {
        setDraft((current) => ({ ...current, [key]: value }))
    }

    function submit(event: FormEvent) {
        event.preventDefault()
        event.stopPropagation()
        onApply(draft)
    }

    const fieldProps = { form: draft, set, translations, idPrefix: 'sheet' }

    return (
        <Dialog
            open={open}
            onClose={onClose}
            title={translations.filters}
            size="sheet"
            closeLabel={translations.close}
            footer={
                <FilterActions
                    clearLabel={translations.clear_all}
                    onClear={onClearAll}
                    applyLabel={translations.apply}
                    applyForm={formId}
                />
            }
        >
            <form id={formId} onSubmit={submit} className="flex flex-col divide-y divide-rule">
                <div className="pb-7">
                    <PropertyTypeChips {...fieldProps} />
                </div>
                <div className="py-7">
                    <PriceFields {...fieldProps} />
                </div>
                <div className="grid grid-cols-2 gap-5 py-7">
                    <BedroomsSelect {...fieldProps} />
                    <BathroomsSelect {...fieldProps} />
                </div>
                <div className="py-7">
                    <CityField {...fieldProps} />
                </div>
                <div className="py-7">
                    <AreaFields {...fieldProps} />
                </div>
                <div className="pt-7">
                    <AmenityChips {...fieldProps} amenityLabels={amenityLabels} />
                </div>
            </form>
        </Dialog>
    )
}
