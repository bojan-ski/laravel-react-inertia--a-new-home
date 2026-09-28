import type { ListingDetailRowProps } from '@/types/listing'

export default function ListingDetailRow({ label, value }: ListingDetailRowProps) {
    return (
        <div className="flex justify-between gap-6 border-b border-border pb-4">
            <dt className="text-text-muted">{label}</dt>
            <dd className="text-right text-text">{value}</dd>
        </div>
    )
}
