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
    note,
    cancelSlot,
}: AgencyFormProps) {
    return (
        <form onSubmit={onSubmit} className="space-y-12">
            <section>
                <h2 className="font-display text-xl font-semibold text-text">
                    {translations.sections.identity}
                </h2>

                <div className="mt-6 flex flex-col gap-5">
                    <TextField
                        id="name"
                        type="text"
                        label={translations.fields.name}
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                    />

                    <div className="flex flex-col gap-1.5">
                        <label htmlFor="description" className="text-sm font-medium text-text-soft">
                            {translations.fields.description}
                        </label>
                        <textarea
                            id="description"
                            rows={5}
                            className="w-full rounded-lg border border-border bg-surface px-4 py-3 text-text outline-none transition-colors focus:border-brand"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                        {errors.description && (
                            <p className="text-sm text-status-rejected">{errors.description}</p>
                        )}
                    </div>
                </div>
            </section>

            <div className="border-t border-border" />

            <section>
                <h2 className="font-display text-xl font-semibold text-text">
                    {translations.sections.business}
                </h2>
                <p className="mt-1 text-sm text-text-muted">{translations.fields.business_note}</p>

                <div className="mt-6 flex flex-col gap-5">
                    <TextField
                        id="legal_name"
                        type="text"
                        label={translations.fields.legal_name}
                        value={data.legal_name}
                        onChange={(e) => setData('legal_name', e.target.value)}
                        error={errors.legal_name}
                    />

                    <TextField
                        id="vat_id"
                        type="text"
                        label={translations.fields.vat_id}
                        placeholder={translations.fields.vat_id_placeholder}
                        value={data.vat_id}
                        onChange={(e) => setData('vat_id', e.target.value)}
                        error={errors.vat_id}
                    />

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <TextField
                            id="registration_number"
                            type="text"
                            label={translations.fields.registration_number}
                            value={data.registration_number}
                            onChange={(e) => setData('registration_number', e.target.value)}
                            error={errors.registration_number}
                        />

                        <div className="flex flex-col gap-1.5">
                            <label htmlFor="registration_country" className="text-sm font-medium text-text-soft">
                                {translations.fields.registration_country}
                            </label>
                            <select
                                id="registration_country"
                                value={data.registration_country}
                                onChange={(e) => setData('registration_country', e.target.value)}
                                className="w-full rounded-lg border border-border bg-surface px-4 py-3 text-text outline-none transition-colors focus:border-brand"
                            >
                                <option value="">{translations.fields.registration_country_placeholder}</option>
                                {Object.entries(countries).map(([code, name]) => (
                                    <option key={code} value={code}>
                                        {name}
                                    </option>
                                ))}
                            </select>
                            {errors.registration_country && (
                                <p className="text-sm text-status-rejected">{errors.registration_country}</p>
                            )}
                        </div>
                    </div>

                    <TextField
                        id="broker_register_number"
                        type="text"
                        label={translations.fields.broker_register_number}
                        value={data.broker_register_number}
                        onChange={(e) => setData('broker_register_number', e.target.value)}
                        error={errors.broker_register_number}
                    />
                </div>
            </section>

            <div className="border-t border-border" />

            <section>
                <h2 className="font-display text-xl font-semibold text-text">
                    {translations.sections.contact}
                </h2>

                <div className="mt-6 flex flex-col gap-5">
                    <div className="flex flex-col gap-1.5">
                        <TextField
                            id="email"
                            type="email"
                            label={translations.fields.email}
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />
                        <p className="text-sm text-text-muted">{translations.fields.email_note}</p>
                    </div>

                    <TextField
                        id="phone"
                        type="text"
                        label={translations.fields.phone}
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        error={errors.phone}
                    />

                    <TextField
                        id="website"
                        type="text"
                        label={translations.fields.website}
                        placeholder="https://"
                        value={data.website}
                        onChange={(e) => setData('website', e.target.value)}
                        error={errors.website}
                    />

                    <TextField
                        id="address"
                        type="text"
                        label={translations.fields.address}
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                        error={errors.address}
                    />
                </div>
            </section>

            <div className="flex flex-col gap-4">
                {note}

                <div className="flex flex-col-reverse items-stretch gap-4 sm:flex-row sm:items-center sm:justify-end">
                    {cancelSlot}

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-full bg-brand px-6 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-60 sm:w-auto"
                    >
                        {submitLabel}
                    </button>
                </div>
            </div>
        </form>
    )
}
