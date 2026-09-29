import { useId, useState } from 'react'
import { formatPrice } from '@/Components/Listings/listingFormat'
import { estimateMortgage, parseAmount } from '@/Components/Listings/mortgage'
import type { MortgageCalculatorTranslations, MortgageDefaults } from '@/types/listing'

const controlClass =
    'w-full rounded-lg border border-border bg-surface px-4 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand'

export interface MortgageCalculatorProps {
    price: string
    defaults: MortgageDefaults
    translations: MortgageCalculatorTranslations
}

export default function MortgageCalculator({ price, defaults, translations }: MortgageCalculatorProps) {
    const id = useId()
    const [priceInput, setPriceInput] = useState(String(Number(price)))
    const [downPaymentInput, setDownPaymentInput] = useState(String(defaults.down_payment_percent))
    const [rateInput, setRateInput] = useState(String(defaults.interest_rate))
    const [termYears, setTermYears] = useState(defaults.term_years)

    const estimate = estimateMortgage(
        parseAmount(priceInput),
        parseAmount(downPaymentInput),
        parseAmount(rateInput),
        termYears,
    )

    const format = (value: number) => formatPrice(String(value), 'EUR')
    const unavailable = translations.unavailable

    return (
        <section className="rounded-2xl border border-border bg-surface p-6">
            <h2 className="font-display text-xl font-semibold text-text">{translations.heading}</h2>

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="flex flex-col gap-1.5">
                    <label htmlFor={`${id}-price`} className="text-sm font-medium text-text">
                        {translations.price}
                    </label>
                    <input
                        id={`${id}-price`}
                        type="number"
                        min="0"
                        step="1000"
                        inputMode="decimal"
                        value={priceInput}
                        onChange={(event) => setPriceInput(event.target.value)}
                        className={controlClass}
                    />
                </div>

                <div className="flex flex-col gap-1.5">
                    <label htmlFor={`${id}-down-payment`} className="text-sm font-medium text-text">
                        {translations.down_payment}
                    </label>
                    <input
                        id={`${id}-down-payment`}
                        type="number"
                        min="0"
                        max="100"
                        step="1"
                        inputMode="decimal"
                        value={downPaymentInput}
                        onChange={(event) => setDownPaymentInput(event.target.value)}
                        className={controlClass}
                    />
                </div>

                <div className="flex flex-col gap-1.5">
                    <label htmlFor={`${id}-rate`} className="text-sm font-medium text-text">
                        {translations.interest_rate}
                    </label>
                    <input
                        id={`${id}-rate`}
                        type="number"
                        min="0"
                        step="0.1"
                        inputMode="decimal"
                        value={rateInput}
                        onChange={(event) => setRateInput(event.target.value)}
                        className={controlClass}
                    />
                </div>

                <div className="flex flex-col gap-1.5">
                    <label htmlFor={`${id}-term`} className="text-sm font-medium text-text">
                        {translations.term}
                    </label>
                    <select
                        id={`${id}-term`}
                        value={termYears}
                        onChange={(event) => setTermYears(Number(event.target.value))}
                        className={controlClass}
                    >
                        {defaults.term_options.map((years) => (
                            <option key={years} value={years}>
                                {translations.term_option.replace(':years', String(years))}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="mt-6 border-t border-border pt-5" aria-live="polite">
                <p className="text-sm text-text-muted">{translations.monthly_payment}</p>
                <p className="mt-1 flex items-baseline gap-2">
                    <span className="font-display text-3xl font-semibold text-text">
                        {estimate ? format(estimate.monthlyPayment) : unavailable}
                    </span>
                    <span className="text-sm text-text-muted">{translations.per_month}</span>
                </p>
                <div className="mt-2 flex flex-col gap-0.5 text-sm text-text-muted">
                    <span>{translations.loan_amount.replace(':amount', estimate ? format(estimate.loanAmount) : unavailable)}</span>
                    <span>
                        {translations.total_interest.replace(':amount', estimate ? format(estimate.totalInterest) : unavailable)}
                    </span>
                </div>
            </div>

            <p className="mt-4 text-xs text-text-muted">{translations.disclaimer}</p>
        </section>
    )
}
