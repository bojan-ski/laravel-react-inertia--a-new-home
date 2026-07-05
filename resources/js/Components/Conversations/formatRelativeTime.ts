const DIVISIONS: { amount: number; unit: Intl.RelativeTimeFormatUnit }[] = [
    { amount: 60, unit: 'second' },
    { amount: 60, unit: 'minute' },
    { amount: 24, unit: 'hour' },
    { amount: 30, unit: 'day' },
    { amount: 12, unit: 'month' },
    { amount: Number.POSITIVE_INFINITY, unit: 'year' },
]

export function formatRelativeTime(iso: string): string {
    const formatter = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' })
    let duration = (new Date(iso).getTime() - Date.now()) / 1000

    for (const division of DIVISIONS) {
        if (Math.abs(duration) < division.amount) {
            return formatter.format(Math.round(duration), division.unit)
        }
        duration /= division.amount
    }

    return formatter.format(Math.round(duration), 'year')
}
