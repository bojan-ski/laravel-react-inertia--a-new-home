import type { SkipLinkProps } from '@/types/ui'

export default function SkipLink({ label, targetId = 'main' }: SkipLinkProps) {
    return (
        <a
            href={`#${targetId}`}
            className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-70 focus:border focus:border-ink focus:bg-paper focus:px-4 focus:py-3 focus:text-sm focus:font-medium focus:text-ink"
        >
            {label}
        </a>
    )
}
