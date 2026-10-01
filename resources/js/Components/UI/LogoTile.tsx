import type { LogoTileProps } from '@/types/ui'

const sizeClasses = {
    md: 'size-22 p-3 text-[32px] font-airy tracking-[-0.02em]',
    lg: 'size-30 p-4 text-[44px] font-airy tracking-[-0.02em]',
    xl: 'size-40 p-5 text-[56px] font-airy tracking-[-0.02em]',
    row: 'size-14 p-2 text-xl font-airy lg:size-22 lg:p-3 lg:text-[32px] lg:tracking-[-0.02em]',
    queue: 'size-14 p-2 text-xl font-airy md:size-18 md:p-2.5 md:text-[26px]',
    hero: 'size-20 p-2.5 text-[32px] font-extralight lg:size-40 lg:p-4.5 lg:text-[64px] lg:tracking-[-0.03em]',
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
            className={`flex shrink-0 items-end bg-forest-50 leading-none text-forest-800 ${sizeClasses[size]}`}
        >
            {initialsOf(name)}
        </span>
    )
}
