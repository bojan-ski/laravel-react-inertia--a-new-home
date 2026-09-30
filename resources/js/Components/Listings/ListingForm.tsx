import FieldError from '@/Components/Forms/FieldError'
import FormActionBar from '@/Components/Forms/FormActionBar'
import FormSection from '@/Components/Forms/FormSection'
import NumberedFormLayout from '@/Components/Forms/NumberedFormLayout'
import Checkbox from '@/Components/UI/Checkbox'
import ChipToggle from '@/Components/UI/ChipToggle'
import SegmentedToggle from '@/Components/UI/SegmentedToggle'
import SelectField from '@/Components/UI/SelectField'
import TextAreaField from '@/Components/UI/TextAreaField'
import TextField from '@/Components/UI/TextField'
import type { ListingFormProps } from '@/types/listing'

const SECTION_KEYS = ['basic', 'images', 'location', 'specs', 'amenities', 'contact'] as const

type SectionKey = (typeof SECTION_KEYS)[number]

export default function ListingForm({
    data,
    setData,
    errors,
    processing,
    submitLabel,
    onSubmit,
    translations,
    propertyTypeLabels,
    amenityLabels,
    countryOptions,
    imagesSection,
    imagesNote,
    barNote,
    contactNote,
    beforeSections,
    hideContactSection = false,
    cancelHref = '/profile',
}: ListingFormProps) {
    const { sections, fields, listing_types } = translations
    const amenitiesError =
        errors.amenities ?? Object.entries(errors).find(([key]) => key.startsWith('amenities.'))?.[1]
    const sectionLinks = SECTION_KEYS.map((key) => ({ id: `section-${key}`, label: sections[key] }))

    function section(key: SectionKey) {
        return {
            id: `section-${key}`,
            number: String(SECTION_KEYS.indexOf(key) + 1).padStart(2, '0'),
            title: sections[key],
        }
    }

    function toggleAmenity(amenity: string, checked: boolean) {
        setData(
            'amenities',
            checked ? [...data.amenities, amenity] : data.amenities.filter((value) => value !== amenity),
        )
    }

    return (
        <form onSubmit={onSubmit}>
            {beforeSections && (
                <div className="flex flex-col gap-2 px-5 pt-6 md:px-10 lg:pt-0 lg:pb-2 xl:px-16">{beforeSections}</div>
            )}

            <NumberedFormLayout
                sections={sectionLinks}
                indexLabel={translations.sections_label}
                bar={
                    <FormActionBar
                        note={barNote}
                        submitLabel={submitLabel}
                        processing={processing}
                        cancelHref={cancelHref}
                        cancelLabel={translations.cancel}
                    />
                }
            >
                <FormSection {...section('basic')}>
                    <div className="col-span-2">
                        <TextField
                            id="title"
                            type="text"
                            label={fields.title}
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            error={errors.title}
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <SegmentedToggle
                            name="listing_type"
                            legend={fields.listing_type}
                            showLegend
                            options={[
                                { value: 'sale', label: listing_types.sale },
                                { value: 'rent', label: listing_types.rent },
                            ]}
                            value={data.listing_type}
                            onChange={(value) => setData('listing_type', value)}
                        />
                        <FieldError message={errors.listing_type} />
                    </div>

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

                    <fieldset className="col-span-2 flex min-w-0 flex-col gap-1.5">
                        <legend className="mb-2.5 text-xs tracking-[0.04em] text-ink-muted">
                            {fields.property_type}
                        </legend>
                        <div className="flex flex-wrap gap-2">
                            {Object.entries(propertyTypeLabels).map(([type, label]) => (
                                <ChipToggle
                                    key={type}
                                    type="radio"
                                    name="property_type"
                                    value={type}
                                    label={label}
                                    checked={data.property_type === type}
                                    onChange={() => setData('property_type', type)}
                                />
                            ))}
                        </div>
                        <FieldError message={errors.property_type} />
                    </fieldset>

                    <div className="col-span-2">
                        <TextAreaField
                            id="description"
                            rows={6}
                            label={fields.description}
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            error={errors.description}
                        />
                    </div>
                </FormSection>

                <FormSection {...section('images')} note={imagesNote}>
                    {imagesSection}
                </FormSection>

                <FormSection {...section('location')}>
                    <SelectField
                        id="country"
                        label={fields.country}
                        value={data.country}
                        onChange={(e) => setData('country', e.target.value)}
                        error={errors.country}
                    >
                        <option value="" disabled>
                            {fields.country_placeholder}
                        </option>
                        {countryOptions.map((country) => (
                            <option key={country} value={country}>
                                {country}
                            </option>
                        ))}
                    </SelectField>
                    <TextField
                        id="city"
                        type="text"
                        label={fields.city}
                        value={data.city}
                        onChange={(e) => setData('city', e.target.value)}
                        error={errors.city}
                    />
                    <TextField
                        id="address_line"
                        type="text"
                        label={fields.address_line}
                        value={data.address_line}
                        onChange={(e) => setData('address_line', e.target.value)}
                        error={errors.address_line}
                    />
                    <TextField
                        id="postal_code"
                        type="text"
                        label={fields.postal_code}
                        value={data.postal_code}
                        onChange={(e) => setData('postal_code', e.target.value)}
                        error={errors.postal_code}
                    />
                </FormSection>

                <FormSection {...section('specs')}>
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
                    <TextField
                        id="area_value"
                        type="number"
                        min={0}
                        step="0.01"
                        label={fields.area}
                        value={data.area_value}
                        onChange={(e) => setData('area_value', e.target.value)}
                        error={errors.area_value}
                    />
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
                </FormSection>

                <FormSection {...section('amenities')}>
                    <div className="col-span-2 flex flex-col gap-2">
                        <div className="grid grid-cols-1 gap-x-6 md:grid-cols-3">
                            {Object.entries(amenityLabels).map(([amenity, label]) => (
                                <Checkbox
                                    key={amenity}
                                    value={amenity}
                                    checked={data.amenities.includes(amenity)}
                                    onChange={(e) => toggleAmenity(amenity, e.target.checked)}
                                    className="min-h-11 cursor-pointer border-b border-rule py-3 text-[15px] font-book"
                                >
                                    {label}
                                </Checkbox>
                            ))}
                        </div>
                        <FieldError message={amenitiesError} />
                    </div>
                </FormSection>

                {hideContactSection ? (
                    <FormSection {...section('contact')} note={contactNote} />
                ) : (
                    <FormSection {...section('contact')} note={translations.contact_note}>
                        <div className="col-span-2 lg:col-span-1">
                            <TextField
                                id="contact_name"
                                type="text"
                                label={fields.contact_name}
                                value={data.contact_name}
                                onChange={(e) => setData('contact_name', e.target.value)}
                                error={errors.contact_name}
                            />
                        </div>
                        <div className="col-span-2 lg:col-span-1">
                            <TextField
                                id="contact_email"
                                type="email"
                                label={fields.contact_email}
                                value={data.contact_email}
                                onChange={(e) => setData('contact_email', e.target.value)}
                                error={errors.contact_email}
                            />
                        </div>
                        <div className="col-span-2">
                            <TextField
                                id="contact_phone"
                                type="text"
                                label={fields.contact_phone}
                                value={data.contact_phone}
                                onChange={(e) => setData('contact_phone', e.target.value)}
                                error={errors.contact_phone}
                            />
                        </div>
                    </FormSection>
                )}
            </NumberedFormLayout>
        </form>
    )
}
