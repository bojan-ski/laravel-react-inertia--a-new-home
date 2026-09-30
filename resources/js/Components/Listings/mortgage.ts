import { useState } from 'react'
import type { MortgageDefaults, MortgageEstimate, MortgageState } from '@/types/listing'

export function parseAmount(value: string): number {
    return value.trim() === '' ? Number.NaN : Number(value)
}

export function estimateMortgage(
    price: number,
    downPaymentPercent: number,
    annualRatePercent: number,
    years: number,
): MortgageEstimate | null {
    const inputs = [price, downPaymentPercent, annualRatePercent, years]

    if (
        !inputs.every(Number.isFinite) ||
        price <= 0 ||
        downPaymentPercent < 0 ||
        downPaymentPercent >= 100 ||
        annualRatePercent < 0 ||
        years <= 0
    ) {
        return null
    }

    const loanAmount = price * (1 - downPaymentPercent / 100)
    const months = years * 12
    const monthlyRate = annualRatePercent / 12 / 100
    const monthlyPayment =
        monthlyRate === 0 ? loanAmount / months : (loanAmount * monthlyRate) / (1 - (1 + monthlyRate) ** -months)

    if (!Number.isFinite(monthlyPayment)) {
        return null
    }

    return {
        loanAmount,
        monthlyPayment,
        totalInterest: monthlyPayment * months - loanAmount,
    }
}

export function useMortgageEstimate(price: string, defaults: MortgageDefaults): MortgageState {
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

    return {
        priceInput,
        setPriceInput,
        downPaymentInput,
        setDownPaymentInput,
        rateInput,
        setRateInput,
        termYears,
        setTermYears,
        estimate,
    }
}
