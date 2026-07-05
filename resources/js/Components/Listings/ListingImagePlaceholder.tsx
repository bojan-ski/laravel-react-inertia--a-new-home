import { ImageIcon } from 'lucide-react'
import type { ListingImagePlaceholderProps } from '@/types/listing'

export default function ListingImagePlaceholder({ className = '' }: ListingImagePlaceholderProps) {
    return (
        <div className={`flex size-full items-center justify-center bg-surface-muted ${className}`}>
            <ImageIcon className="size-8 text-text-muted" aria-hidden="true" />
        </div>
    )
}
