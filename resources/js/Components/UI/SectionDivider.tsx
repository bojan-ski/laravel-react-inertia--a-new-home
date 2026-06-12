import type { SectionDividerProps } from '@/types/layout'

export default function SectionDivider({ className = '' }: SectionDividerProps) {
    return (
        <div className={`flex items-center gap-4 ${className}`} aria-hidden="true">
            <span className="h-px flex-1 bg-border" />
            <span className="text-sm leading-none text-accent">&#10022;</span>
            <span className="h-px flex-1 bg-border" />
        </div>
    )
}
