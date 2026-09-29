import type { LogoTileProps } from '@/types/ui'

const sizeClasses = {
    md: 'size-22 p-3 text-[32px]',
    lg: 'size-30 p-4 text-[44px]',
    xl: 'size-40 p-5 text-[56px]',
}

function initialsOf(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase()
}

export default function LogoTile({ name, size = 'md' }: LogoTileProps) {
    return (
        <span
            aria-hidden="true"
            className={`flex shrink-0 items-end bg-forest-50 leading-none font-airy tracking-[-0.02em] text-forest-800 ${sizeClasses[size]}`}
        >
            {initialsOf(name)}
        </span>
    )
}
