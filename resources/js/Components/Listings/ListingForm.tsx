import { Link } from '@inertiajs/react'
import TextField from '@/Components/UI/TextField'
import type { ListingFormProps } from '@/types/listing'

const PROPERTY_TYPES = [
    'house', 'flat', 'apartment', 'cottage', 'villa',
    'condo', 'land', 'commercial', 'other',
]

const CURRENCIES = ['EUR', 'USD', 'GBP']

const AREA_UNITS = ['m²', 'sqft']

const controlClasses =
    'w-full rounded-lg border border-border bg-surface px-4 py-3 text-text outline-none transition-colors focus:border-brand'

export default function ListingForm({
    data,
    setData,
    errors,
    processing,
    submitLabel,
    onSubmit,
    translations,
    propertyTypeLabels,
    headerSlot,
    hideContactSection = false,
    cancelHref = '/profile',
}: ListingFormProps) {
    const { sections, fields, listing_types } = translations

    return (
        <form onSubmit={onSubmit} className="space-y-12">
            {headerSlot}

            <section className="space-y-5">
                <h2 className="font-display text-xl font-semibold text-text">{sections.basic}</h2>

                <TextField
                    id="title"
                    type="text"
                    label={fields.title}
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    error={errors.title}
                />

                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div className="flex flex-col gap-1.5">
                        <label htmlFor="listing_type" className="text-sm font-medium text-text-soft">
                            {fields.listing_type}
                        </label>
                        <select
                            id="listing_type"
                            className={controlClasses}
                            value={data.listing_type}
                            onChange={(e) => setData('listing_type', e.target.value)}
                        >
                            <option value="sale">{listing_types.sale}</option>
                            <option value="rent">{listing_types.rent}</option>
                        </select>
                        {errors.listing_type && <p className="text-sm text-status-rejected">{errors.listing_type}</p>}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <label htmlFor="property_type" className="text-sm font-medium text-text-soft">
                            {fields.property_type}
                        </label>
                        <select
                            id="property_type"
                            className={controlClasses}
                            value={data.property_type}
                            onChange={(e) => setData('property_type', e.target.value)}
                        >
                            {PROPERTY_TYPES.map((type) => (
                                <option key={type} value={type}>
                                    {propertyTypeLabels[type]}
                                </option>
                            ))}
                        </select>
                        {errors.property_type && <p className="text-sm text-status-rejected">{errors.property_type}</p>}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <TextField
                        id="price"
                        type="number"
                        min={0}
                        step="0.01"
                        label={fields.price}
                        value={data.price}
                        onChange={(e) => setData('price', e.target.value)}
                        error={errors.price}
                    />

                    <div className="flex flex-col gap-1.5">
                        <label htmlFor="currency" className="text-sm font-medium text-text-soft">
                            {fields.currency}
                        </label>
                        <select
                            id="currency"
                            className={controlClasses}
                            value={data.currency}
                            onChange={(e) => setData('currency', e.target.value)}
                        >
                            {CURRENCIES.map((currency) => (
                                <option key={currency} value={currency}>
                                    {currency}
                                </option>
                            ))}
                        </select>
                        {errors.currency && <p className="text-sm text-status-rejected">{errors.currency}</p>}
                    </div>
                </div>

                <div className="flex flex-col gap-1.5">
                    <label htmlFor="description" className="text-sm font-medium text-text-soft">
                        {fields.description}
                    </label>
                    <textarea
                        id="description"
                        rows={7}
                        className={controlClasses}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                    />
                    {errors.description && <p className="text-sm text-status-rejected">{errors.description}</p>}
                </div>
            </section>

            <section className="space-y-5 border-t border-border pt-12">
                <h2 className="font-display text-xl font-semibold text-text">{sections.location}</h2>

                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <TextField
                        id="country"
                        type="text"
                        label={fields.country}
                        value={data.country}
                        onChange={(e) => setData('country', e.target.value)}
                        error={errors.country}
                    />
                    <TextField
                        id="city"
                        type="text"
                        label={fields.city}
                        value={data.city}
                        onChange={(e) => setData('city', e.target.value)}
                        error={errors.city}
                    />
                </div>

                <TextField
                    id="address_line"
                    type="text"
                    label={fields.address_line}
                    value={data.address_line}
                    onChange={(e) => setData('address_line', e.target.value)}
                    error={errors.address_line}
                />

                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <TextField
                        id="postal_code"
                        type="text"
                        label={fields.postal_code}
                        value={data.postal_code}
                        onChange={(e) => setData('postal_code', e.target.value)}
                        error={errors.postal_code}
                    />
                    <div className="hidden md:block" aria-hidden="true" />
                </div>
            </section>

            <section className="space-y-5 border-t border-border pt-12">
                <h2 className="font-display text-xl font-semibold text-text">{sections.specs}</h2>

                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <TextField
                        id="bedrooms"
                        type="number"
                        min={0}
                        label={fields.bedrooms}
                        value={data.bedrooms}
                        onChange={(e) => setData('bedrooms', e.target.value)}
                        error={errors.bedrooms}
                    />
                    <TextField
                        id="bathrooms"
                        type="number"
                        min={0}
                        label={fields.bathrooms}
                        value={data.bathrooms}
                        onChange={(e) => setData('bathrooms', e.target.value)}
                        error={errors.bathrooms}
                    />

                    <div className="flex flex-col gap-1.5">
                        <label htmlFor="area_value" className="text-sm font-medium text-text-soft">
                            {fields.area}
                        </label>
                        <div className="flex gap-2">
                            <input
                                id="area_value"
                                type="number"
                                min={0}
                                step="0.01"
                                className={controlClasses}
                                value={data.area_value}
                                onChange={(e) => setData('area_value', e.target.value)}
                            />
                            <select
                                aria-label={fields.area}
                                className="rounded-lg border border-border bg-surface px-3 py-3 text-text outline-none transition-colors focus:border-brand"
                                value={data.area_unit}
                                onChange={(e) => setData('area_unit', e.target.value)}
                            >
                                {AREA_UNITS.map((unit) => (
                                    <option key={unit} value={unit}>
                                        {unit}
                                    </option>
                                ))}
                            </select>
                        </div>
                        {errors.area_value && <p className="text-sm text-status-rejected">{errors.area_value}</p>}
                    </div>

                    <TextField
                        id="year_built"
                        type="number"
                        min={1800}
                        max={2100}
                        label={fields.year_built}
                        value={data.year_built}
                        onChange={(e) => setData('year_built', e.target.value)}
                        error={errors.year_built}
                    />
                </div>
            </section>

            {!hideContactSection && (
                <section className="space-y-5 border-t border-border pt-12">
                    <h2 className="font-display text-xl font-semibold text-text">{sections.contact}</h2>
                    <p className="text-sm text-text-soft">{translations.contact_note}</p>

                    <TextField
                        id="contact_name"
                        type="text"
                        label={fields.contact_name}
                        value={data.contact_name}
                        onChange={(e) => setData('contact_name', e.target.value)}
                        error={errors.contact_name}
                    />

                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <TextField
                            id="contact_email"
                            type="email"
                            label={fields.contact_email}
                            value={data.contact_email}
                            onChange={(e) => setData('contact_email', e.target.value)}
                            error={errors.contact_email}
                        />
                        <TextField
                            id="contact_phone"
                            type="text"
                            label={fields.contact_phone}
                            value={data.contact_phone}
                            onChange={(e) => setData('contact_phone', e.target.value)}
                            error={errors.contact_phone}
                        />
                    </div>
                </section>
            )}

            <div className="border-t border-border pt-8">
                <div className="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href={cancelHref}
                        className="inline-flex items-center justify-center px-5 py-3 text-sm font-medium text-text-soft transition-colors hover:text-text"
                    >
                        {translations.cancel}
                    </Link>
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center justify-center rounded-full bg-brand px-6 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-60"
                    >
                        {submitLabel}
                    </button>
                </div>
                <p className="mt-3 text-right text-sm text-text-muted">{translations.submit_note}</p>
            </div>
        </form>
    )
}
