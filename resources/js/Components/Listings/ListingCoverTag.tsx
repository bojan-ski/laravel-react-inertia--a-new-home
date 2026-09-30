import type { ListingCoverTagProps } from '@/types/listing'

export default function ListingCoverTag({ label }: ListingCoverTagProps) {
    return (
        <span className="pointer-events-none absolute top-0 left-0 bg-paper px-2 py-1.25 text-[11px] tracking-widest uppercase">
            {label}
        </span>
    )
}
