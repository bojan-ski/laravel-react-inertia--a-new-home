import { useId } from 'react'
import { formatPrice } from '@/Components/Listings/listingFormat'
import Kicker from '@/Components/UI/Kicker'
import SegmentedToggle from '@/Components/UI/SegmentedToggle'
import type { MortgageCalculatorProps } from '@/types/listing'

interface MortgageFieldProps {
    id: string
    label: string
    value: string
    onChange: (value: string) => void
    step: string
    compact: boolean
}

function MortgageField({ id, label, value, onChange, step, compact }: MortgageFieldProps) {
    return (
        <div className="flex min-w-0 flex-col gap-1 lg:gap-1.5">
            <label htmlFor={id} className="text-xs text-ink-muted">
                {label}
            </label>
            <input
                id={id}
                type="number"
                min="0"
                step={step}
                inputMode="decimal"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className={`w-full min-w-0 border-b border-ink bg-transparent font-light text-ink transition-colors duration-150 focus:border-forest ${
                    compact ? 'min-h-10 text-[17px]' : 'py-1.5 text-xl'
                }`}
            />
        </div>
    )
}

export default function MortgageCalculator({ mortgage, termOptions, translations, variant }: MortgageCalculatorProps) {
    const id = useId()
    const compact = variant === 'compact'
    const { estimate } = mortgage
    const format = (value: number) => formatPrice(String(value), 'EUR')
    const unavailable = translations.unavailable
    const monthly = estimate ? format(estimate.monthlyPayment) : unavailable

    const downPaymentField = (
        <MortgageField
            id={`${id}-down-payment`}
            label={translations.down_payment}
            value={mortgage.downPaymentInput}
            onChange={mortgage.setDownPaymentInput}
            step="1"
            compact={compact}
        />
    )

    const rateField = (
        <MortgageField
            id={`${id}-rate`}
            label={translations.interest_rate}
            value={mortgage.rateInput}
            onChange={mortgage.setRateInput}
            step="0.1"
            compact={compact}
        />
    )

    const termToggle = (
        <SegmentedToggle
            name={`${id}-term`}
            legend={translations.term}
            options={termOptions.map((years) => ({
                value: String(years),
                label: (compact ? translations.term_option_short : translations.term_option).replace(
                    ':years',
                    String(years),
                ),
            }))}
            value={String(mortgage.termYears)}
            onChange={(value) => mortgage.setTermYears(Number(value))}
            size={compact ? 'spread' : 'wide'}
        />
    )

    if (compact) {
        return (
            <section className="flex flex-col gap-4.5 bg-sand px-5 py-6">
                <Kicker as="h2" size="sm">
                    {translations.heading}
                </Kicker>

                <div className="flex flex-col gap-1" aria-live="polite">
                    <span className="text-xs text-ink-muted">{translations.monthly_payment}</span>
                    <span className="text-[40px] leading-none font-extralight tracking-[-0.03em]">{monthly}</span>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    {downPaymentField}
                    {rateField}
                </div>

                {termToggle}

                <p className="text-xs text-ink/62">{translations.disclaimer}</p>
            </section>
        )
    }

    return (
        <div className="flex flex-col gap-8 bg-sand p-9">
            <div className="grid grid-cols-3 gap-6">
                <MortgageField
                    id={`${id}-price`}
                    label={translations.price}
                    value={mortgage.priceInput}
                    onChange={mortgage.setPriceInput}
                    step="1000"
                    compact={false}
                />
                {downPaymentField}
                {rateField}
            </div>

            <div className="flex flex-col gap-2.5">
                <span className="text-xs text-ink-muted" aria-hidden="true">
                    {translations.term}
                </span>
                {termToggle}
            </div>

            <div
                className="flex flex-wrap items-end justify-between gap-x-6 gap-y-3 border-t border-rule pt-7"
                aria-live="polite"
            >
                <div className="flex flex-col gap-1.5">
                    <span className="text-xs text-ink-muted">{translations.monthly_payment}</span>
                    <span className="text-[56px] leading-none font-extralight tracking-[-0.03em]">
                        {monthly}
                        <span className="text-base font-book tracking-normal"> {translations.per_month}</span>
                    </span>
                </div>
                <div className="flex flex-col gap-1 text-right text-sm text-ink/72">
                    <span>
                        {translations.loan_amount.replace(':amount', estimate ? format(estimate.loanAmount) : unavailable)}
                    </span>
                    <span>
                        {translations.total_interest.replace(
                            ':amount',
                            estimate ? format(estimate.totalInterest) : unavailable,
                        )}
                    </span>
                </div>
            </div>

            <p className="text-[13px] text-ink/62">{translations.disclaimer}</p>
        </div>
    )
}
