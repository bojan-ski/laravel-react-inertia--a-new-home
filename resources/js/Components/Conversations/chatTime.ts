import type { DaySeparatorLabels } from '@/types/conversation'

const LOCALE = 'en-GB'
const DAY_MS = 24 * 60 * 60 * 1000

function startOfDay(date: Date): number {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime()
}

function daysBefore(date: Date, now: Date): number {
    return Math.round((startOfDay(now) - startOfDay(date)) / DAY_MS)
}

export function isSameDay(first: string, second: string): boolean {
    return startOfDay(new Date(first)) === startOfDay(new Date(second))
}

export function formatMessageTime(iso: string): string {
    return new Date(iso).toLocaleTimeString(LOCALE, { hour: '2-digit', minute: '2-digit' })
}

export function formatInboxTime(iso: string, yesterday: string): string {
    const date = new Date(iso)
    const now = new Date()
    const days = daysBefore(date, now)

    if (days <= 0) {
        return formatMessageTime(iso)
    }

    if (days === 1) {
        return yesterday
    }

    return date.toLocaleDateString(LOCALE, {
        day: 'numeric',
        month: 'short',
        year: date.getFullYear() === now.getFullYear() ? undefined : 'numeric',
    })
}

export function formatDaySeparator(iso: string, labels: DaySeparatorLabels): string {
    const date = new Date(iso)
    const now = new Date()
    const days = daysBefore(date, now)

    if (days <= 0) {
        return labels.today
    }

    if (days === 1) {
        return labels.yesterday
    }

    if (days < 7) {
        return date.toLocaleDateString(LOCALE, { weekday: 'long' })
    }

    return date.toLocaleDateString(LOCALE, {
        day: 'numeric',
        month: 'long',
        year: date.getFullYear() === now.getFullYear() ? undefined : 'numeric',
    })
}
