import type { MortgageEstimate } from '@/types/listing'

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

    if (!inputs.every(Number.isFinite) || price < 0 || annualRatePercent < 0 || years <= 0) {
        return null
    }

    const downPayment = Math.min(Math.max(downPaymentPercent, 0), 100)
    const loanAmount = price * (1 - downPayment / 100)
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
