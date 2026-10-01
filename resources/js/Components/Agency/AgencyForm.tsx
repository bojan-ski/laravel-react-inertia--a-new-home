import FormActionBar from '@/Components/Forms/FormActionBar'
import FormSection from '@/Components/Forms/FormSection'
import SelectField from '@/Components/UI/SelectField'
import TextAreaField from '@/Components/UI/TextAreaField'
import TextField from '@/Components/UI/TextField'
import type { AgencyFormProps } from '@/types/agency'

export default function AgencyForm({
    data,
    setData,
    errors,
    processing,
    submitLabel,
    onSubmit,
    translations,
    countries,
    barNote,
    cancelHref,
    cancelLabel,
}: AgencyFormProps) {
    const { sections, fields } = translations

    return (
        <form onSubmit={onSubmit}>
            <div className="px-5 py-6 md:px-10 lg:pt-0 lg:pb-12 xl:px-16">
                <FormSection id="section-identity" number="01" title={sections.identity}>
                    <div className="col-span-2">
                        <TextField
                            id="name"
                            aria-required
                            type="text"
                            label={fields.name}
                            placeholder={fields.name_placeholder}
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                        />
                    </div>

                    <div className="col-span-2">
                        <TextAreaField
                            id="description"
                            rows={5}
                            label={fields.description}
                            placeholder={fields.description_placeholder}
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            error={errors.description}
                        />
                    </div>
                </FormSection>

                <FormSection
                    id="section-business"
                    number="02"
                    title={sections.business}
                    note={fields.business_note}
                >
                    <div className="col-span-2">
                        <TextField
                            id="legal_name"
                            aria-required
                            type="text"
                            label={fields.legal_name}
                            value={data.legal_name}
                            onChange={(e) => setData('legal_name', e.target.value)}
                            error={errors.legal_name}
                        />
                    </div>

                    <div className="col-span-2 lg:col-span-1">
                        <TextField
                            id="registration_number"
                            aria-required
                            type="text"
                            label={fields.registration_number}
                            value={data.registration_number}
                            onChange={(e) => setData('registration_number', e.target.value)}
                            error={errors.registration_number}
                        />
                    </div>

                    <div className="col-span-2 lg:col-span-1">
                        <SelectField
                            id="registration_country"
                            aria-required
                            label={fields.registration_country}
                            value={data.registration_country}
                            onChange={(e) => setData('registration_country', e.target.value)}
                            error={errors.registration_country}
                        >
                            <option value="">{fields.registration_country_placeholder}</option>
                            {Object.entries(countries).map(([code, name]) => (
                                <option key={code} value={code}>
                                    {name}
                                </option>
                            ))}
                        </SelectField>
                    </div>

                    <div className="col-span-2 lg:col-span-1">
                        <TextField
                            id="vat_id"
                            type="text"
                            label={fields.vat_id}
                            placeholder={fields.vat_id_placeholder}
                            value={data.vat_id}
                            onChange={(e) => setData('vat_id', e.target.value)}
                            error={errors.vat_id}
                        />
                    </div>

                    <div className="col-span-2 lg:col-span-1">
                        <TextField
                            id="broker_register_number"
                            type="text"
                            label={fields.broker_register_number}
                            value={data.broker_register_number}
                            onChange={(e) => setData('broker_register_number', e.target.value)}
                            error={errors.broker_register_number}
                        />
                    </div>
                </FormSection>

                <FormSection id="section-contact" number="03" title={sections.contact}>
                    <div className="col-span-2 flex flex-col gap-1.5 lg:col-span-1">
                        <TextField
                            id="email"
                            aria-required
                            type="email"
                            label={fields.email}
                            placeholder={fields.email_placeholder}
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />
                        <p className="text-xs text-ink-muted">{fields.email_note}</p>
                    </div>

                    <div className="col-span-2 lg:col-span-1">
                        <TextField
                            id="phone"
                            type="text"
                            label={fields.phone}
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            error={errors.phone}
                        />
                    </div>

                    <div className="col-span-2 lg:col-span-1">
                        <TextField
                            id="website"
                            type="text"
                            label={fields.website}
                            placeholder={fields.website_placeholder}
                            value={data.website}
                            onChange={(e) => setData('website', e.target.value)}
                            error={errors.website}
                        />
                    </div>

                    <div className="col-span-2 lg:col-span-1">
                        <TextField
                            id="address"
                            type="text"
                            label={fields.address}
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            error={errors.address}
                        />
                    </div>
                </FormSection>
            </div>

            <FormActionBar
                note={barNote}
                submitLabel={submitLabel}
                processing={processing}
                cancelHref={cancelHref}
                cancelLabel={cancelLabel}
            />
        </form>
    )
}
