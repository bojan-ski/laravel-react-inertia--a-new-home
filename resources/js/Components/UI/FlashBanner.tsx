import { X } from 'lucide-react'
import type { FlashBannerProps } from '@/types/ui'

export default function FlashBanner({ message, variant, coloredText = false, className = 'mb-6', onDismiss }: FlashBannerProps) {
    const palette =
        variant === 'success'
            ? 'border-status-approved/30 bg-status-approved/10'
            : 'border-status-rejected/30 bg-status-rejected/10'
    const textColor = coloredText ? 'text-status-rejected' : 'text-text'

    const classes = [
        className,
        onDismiss ? 'flex items-start justify-between gap-4' : null,
        'rounded-lg border',
        palette,
        'px-4 py-3 text-sm',
        textColor,
    ]
        .filter(Boolean)
        .join(' ')

    return (
        <div className={classes}>
            <span>{message}</span>
            {onDismiss && (
                <button
                    type="button"
                    onClick={onDismiss}
                    className="text-text-soft transition-colors hover:text-text"
                    aria-label="Dismiss"
                >
                    <X className="size-4" />
                </button>
            )}
        </div>
    )
}
