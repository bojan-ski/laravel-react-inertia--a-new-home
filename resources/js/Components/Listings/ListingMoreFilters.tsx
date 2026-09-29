import type { ListingMoreFiltersProps } from '@/types/listing'

const BEDROOM_OPTIONS = [1, 2, 3, 4, 5]
const BATHROOM_OPTIONS = [1, 2, 3, 4]

const controlClass =
    'w-full rounded-lg border border-border bg-surface px-4 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand'

export default function ListingMoreFilters({ form, set, translations, amenityLabels }: ListingMoreFiltersProps) {
    function toggleAmenity(amenity: string) {
        set(
            'amenities',
            form.amenities.includes(amenity)
                ? form.amenities.filter((value) => value !== amenity)
                : [...form.amenities, amenity]
        )
    }

    return (
        <div className="mt-3 flex flex-col gap-3">
            <div className="flex flex-wrap gap-3">
                <input
                    type="text"
                    value={form.city}
                    onChange={(event) => set('city', event.target.value)}
                    placeholder={translations.city_placeholder}
                    aria-label={translations.city_placeholder}
                    className={`${controlClass} sm:w-48`}
                />
                <input
                    type="number"
                    min="0"
                    value={form.minPrice}
                    onChange={(event) => set('minPrice', event.target.value)}
                    placeholder={translations.min_price}
                    aria-label={translations.min_price}
                    className={`${controlClass} sm:w-40`}
                />
                <input
                    type="number"
                    min="0"
                    value={form.maxPrice}
                    onChange={(event) => set('maxPrice', event.target.value)}
                    placeholder={translations.max_price}
                    aria-label={translations.max_price}
                    className={`${controlClass} sm:w-40`}
                />
                <select
                    value={form.minBedrooms}
                    onChange={(event) => set('minBedrooms', event.target.value)}
                    aria-label={translations.any_beds}
                    className={`${controlClass} px-3 sm:w-36`}
                >
                    <option value="">{translations.any_beds}</option>
                    {BEDROOM_OPTIONS.map((count) => (
                        <option key={count} value={String(count)}>
                            {translations.beds_option.replace(':count', String(count))}
                        </option>
                    ))}
                </select>
                <select
                    value={form.minBathrooms}
                    onChange={(event) => set('minBathrooms', event.target.value)}
                    aria-label={translations.any_baths}
                    className={`${controlClass} px-3 sm:w-36`}
                >
                    <option value="">{translations.any_baths}</option>
                    {BATHROOM_OPTIONS.map((count) => (
                        <option key={count} value={String(count)}>
                            {translations.baths_option.replace(':count', String(count))}
                        </option>
                    ))}
                </select>
                <input
                    type="number"
                    min="0"
                    value={form.minArea}
                    onChange={(event) => set('minArea', event.target.value)}
                    placeholder={translations.min_area}
                    aria-label={translations.min_area}
                    className={`${controlClass} sm:w-32`}
                />
                <input
                    type="number"
                    min="0"
                    value={form.maxArea}
                    onChange={(event) => set('maxArea', event.target.value)}
                    placeholder={translations.max_area}
                    aria-label={translations.max_area}
                    className={`${controlClass} sm:w-32`}
                />
            </div>

            <div role="group" aria-label={translations.amenities_label} className="flex flex-wrap items-center gap-2">
                <span className="mr-1 text-sm font-medium text-text-soft">{translations.amenities_label}</span>
                {Object.entries(amenityLabels).map(([value, label]) => {
                    const isSelected = form.amenities.includes(value)

                    return (
                        <button
                            key={value}
                            type="button"
                            aria-pressed={isSelected}
                            onClick={() => toggleAmenity(value)}
                            className={`rounded-full border px-3 py-1.5 text-sm transition-colors ${
                                isSelected
                                    ? 'border-brand bg-brand-soft text-brand'
                                    : 'border-border text-text-soft hover:text-text'
                            }`}
                        >
                            {label}
                        </button>
                    )
                })}
            </div>
        </div>
    )
}
