import { Check, Info, X } from 'lucide-react'
import type { FlashBannerProps } from '@/types/ui'

const variants = {
    success: { palette: 'bg-forest-50 text-forest-800', Icon: Check },
    info: { palette: 'bg-sand text-ink', Icon: Info },
    error: { palette: 'border border-ink bg-paper text-ink', Icon: Info },
}

export default function FlashBanner({
    message,
    variant,
    className = 'mb-6',
    onDismiss,
    dismissLabel,
}: FlashBannerProps) {
    const { palette, Icon } = variants[variant]

    return (
        <div className={`${className} flex items-start gap-3 px-4.5 py-3.5 text-[15px] ${palette}`}>
            <Icon className="mt-0.5 size-4.5 shrink-0" strokeWidth={1.25} aria-hidden="true" />
            <span className="flex-1">{message}</span>
            {onDismiss && (
                <button
                    type="button"
                    onClick={onDismiss}
                    className="mt-0.5 shrink-0 opacity-70 transition-opacity duration-150 hover:opacity-100"
                    aria-label={dismissLabel}
                >
                    <X className="size-4.5" strokeWidth={1.25} aria-hidden="true" />
                </button>
            )}
        </div>
    )
}
