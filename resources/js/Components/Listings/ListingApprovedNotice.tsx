import { Info } from 'lucide-react'
import type { ListingApprovedNoticeProps } from '@/types/listing'

export default function ListingApprovedNotice({ message }: ListingApprovedNoticeProps) {
    return (
        <div className="flex items-start gap-3.5 bg-forest-50 px-6 py-4.5 text-[15px] leading-normal text-forest-800">
            <Info className="mt-px size-5 shrink-0" strokeWidth={1.25} aria-hidden="true" />
            <p>{message}</p>
        </div>
    )
}
