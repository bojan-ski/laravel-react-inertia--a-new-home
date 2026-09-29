import type { ListingImagePlaceholderProps } from '@/types/listing'

export default function ListingImagePlaceholder({ className = '' }: ListingImagePlaceholderProps) {
    return <div aria-hidden="true" className={`size-full bg-sand ${className}`} />
}
