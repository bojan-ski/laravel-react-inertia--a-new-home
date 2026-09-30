import { BATHROOM_OPTIONS, BEDROOM_OPTIONS } from '@/Components/Listings/listingFilters'
import Button from '@/Components/UI/Button'
import ChipToggle from '@/Components/UI/ChipToggle'
import SelectField from '@/Components/UI/SelectField'
import TextField from '@/Components/UI/TextField'
import type {
    ListingAmenityChipsProps,
    ListingFilterActionsProps,
    ListingFilterFieldsProps,
} from '@/types/listing'

export function PriceFields({ form, set, translations, idPrefix }: ListingFilterFieldsProps) {
    return (
        <div className="grid grid-cols-2 gap-5">
            <TextField
                id={`${idPrefix}-min-price`}
                label={translations.min_price}
                type="number"
                min="0"
                inputMode="numeric"
                value={form.minPrice}
                onChange={(event) => set('minPrice', event.target.value)}
            />
            <TextField
                id={`${idPrefix}-max-price`}
                label={translations.max_price}
                type="number"
                min="0"
                inputMode="numeric"
                value={form.maxPrice}
                onChange={(event) => set('maxPrice', event.target.value)}
            />
        </div>
    )
}

export function AreaFields({ form, set, translations, idPrefix }: ListingFilterFieldsProps) {
    return (
        <div className="grid grid-cols-2 gap-5">
            <TextField
                id={`${idPrefix}-min-area`}
                label={translations.min_area}
                type="number"
                min="0"
                inputMode="numeric"
                value={form.minArea}
                onChange={(event) => set('minArea', event.target.value)}
            />
            <TextField
                id={`${idPrefix}-max-area`}
                label={translations.max_area}
                type="number"
                min="0"
                inputMode="numeric"
                value={form.maxArea}
                onChange={(event) => set('maxArea', event.target.value)}
            />
        </div>
    )
}

export function CityField({ form, set, translations, idPrefix }: ListingFilterFieldsProps) {
    return (
        <TextField
            id={`${idPrefix}-city`}
            label={translations.city_placeholder}
            maxLength={100}
            value={form.city}
            onChange={(event) => set('city', event.target.value)}
        />
    )
}

export function BedroomsSelect({ form, set, translations, idPrefix }: ListingFilterFieldsProps) {
    return (
        <SelectField
            id={`${idPrefix}-beds`}
            label={translations.beds}
            value={form.minBedrooms}
            onChange={(event) => set('minBedrooms', event.target.value)}
        >
            <option value="">{translations.any_beds}</option>
            {BEDROOM_OPTIONS.map((count) => (
                <option key={count} value={String(count)}>
                    {translations.beds_option.replace(':count', String(count))}
                </option>
            ))}
        </SelectField>
    )
}

export function BathroomsSelect({ form, set, translations, idPrefix }: ListingFilterFieldsProps) {
    return (
        <SelectField
            id={`${idPrefix}-baths`}
            label={translations.baths}
            value={form.minBathrooms}
            onChange={(event) => set('minBathrooms', event.target.value)}
        >
            <option value="">{translations.any_baths}</option>
            {BATHROOM_OPTIONS.map((count) => (
                <option key={count} value={String(count)}>
                    {translations.baths_option.replace(':count', String(count))}
                </option>
            ))}
        </SelectField>
    )
}

export function PropertyTypeChips({ form, set, translations, idPrefix }: ListingFilterFieldsProps) {
    const options = [{ value: '', label: translations.all_types }].concat(
        Object.entries(translations.property_types).map(([value, label]) => ({ value, label })),
    )

    return (
        <fieldset>
            <legend className="mb-3 text-xs tracking-[0.04em] text-ink-muted">{translations.property_type_label}</legend>
            <div className="flex flex-wrap gap-2">
                {options.map((option) => (
                    <ChipToggle
                        key={option.value || 'all'}
                        type="radio"
                        name={`${idPrefix}-property-type`}
                        value={option.value}
                        label={option.label}
                        checked={form.propertyType === option.value}
                        onChange={() => set('propertyType', option.value)}
                    />
                ))}
            </div>
        </fieldset>
    )
}

export function AmenityChips({ form, set, translations, amenityLabels, idPrefix }: ListingAmenityChipsProps) {
    function toggle(amenity: string, checked: boolean) {
        set(
            'amenities',
            checked ? [...form.amenities, amenity] : form.amenities.filter((value) => value !== amenity),
        )
    }

    return (
        <fieldset>
            <legend className="mb-3 text-xs tracking-[0.04em] text-ink-muted">{translations.amenities_label}</legend>
            <div className="flex flex-wrap gap-2">
                {Object.entries(amenityLabels).map(([value, label]) => (
                    <ChipToggle
                        key={value}
                        type="checkbox"
                        name={`${idPrefix}-amenities`}
                        value={value}
                        label={label}
                        checked={form.amenities.includes(value)}
                        onChange={(checked) => toggle(value, checked)}
                    />
                ))}
            </div>
        </fieldset>
    )
}

export function FilterActions({ clearLabel, onClear, applyLabel, applyForm }: ListingFilterActionsProps) {
    return (
        <div className="flex gap-2.5 sm:justify-end">
            <Button variant="secondary" size="sm" onClick={onClear} className="flex-1 sm:flex-none">
                {clearLabel}
            </Button>
            <Button type="submit" size="sm" form={applyForm} className="flex-1 sm:flex-none">
                {applyLabel}
            </Button>
        </div>
    )
}
