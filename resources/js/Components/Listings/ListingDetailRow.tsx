import type { ListingDetailRowProps } from '@/types/listing'

export default function ListingDetailRow({ label, value }: ListingDetailRowProps) {
    return (
        <div className="flex justify-between gap-4 border-b border-rule py-3 text-sm @4xl:grid @4xl:grid-cols-[200px_minmax(0,1fr)] @4xl:gap-6 @4xl:py-3.5 @4xl:text-base">
            <dt className="text-ink/62">{label}</dt>
            <dd className="text-right @4xl:text-left">{value}</dd>
        </div>
    )
}
