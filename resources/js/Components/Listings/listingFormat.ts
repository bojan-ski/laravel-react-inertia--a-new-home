const currencySymbols: Record<string, string> = {
    EUR: '€',
    USD: '$',
    GBP: '£',
}

export function formatPrice(price: string, currency: string): string {
    const amount = Number(price)
    const formatted = Number.isNaN(amount)
        ? price
        : amount.toLocaleString('en-US', { maximumFractionDigits: 0 })
    const symbol = currencySymbols[currency]

    return symbol ? `${symbol}${formatted}` : `${currency} ${formatted}`
}

export function formatArea(value: string, unit: string | null): string {
    const amount = Number(value)
    const formatted = Number.isNaN(amount) ? value : amount.toLocaleString('en-US')

    return unit ? `${formatted} ${unit}` : formatted
}

export function formatDate(iso: string | null, style: 'short' | 'long' = 'short'): string {
    if (!iso) {
        return ''
    }

    return new Date(iso).toLocaleDateString('en-US', {
        month: style,
        day: 'numeric',
        year: 'numeric',
    })
}

export function formatDateTime(iso: string | null): string {
    if (!iso) {
        return ''
    }

    return new Date(iso).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    })
}
