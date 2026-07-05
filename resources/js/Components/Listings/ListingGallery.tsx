import { useState } from 'react'
import type { ListingGalleryProps } from '@/types/listing'

export default function ListingGallery({ images, title }: ListingGalleryProps) {
    const [activeIndex, setActiveIndex] = useState(0)

    if (images.length === 0) {
        return null
    }

    const active = images[activeIndex] ?? images[0]

    return (
        <div className="flex flex-col gap-4">
            <div className="aspect-[16/9] w-full overflow-hidden rounded-2xl border border-border bg-surface-muted">
                <img src={active.url} alt={active.altText || title} className="size-full object-cover" />
            </div>

            {images.length > 1 && (
                <div className="flex gap-3 overflow-x-auto pb-1">
                    {images.map((image, index) => (
                        <button
                            key={image.id}
                            type="button"
                            onClick={() => setActiveIndex(index)}
                            aria-current={index === activeIndex}
                            className={`aspect-[4/3] h-20 shrink-0 overflow-hidden rounded-lg border transition-colors ${
                                index === activeIndex
                                    ? 'border-brand ring-2 ring-brand/30'
                                    : 'border-border hover:border-border-strong'
                            }`}
                        >
                            <img src={image.url} alt={image.altText || title} className="size-full object-cover" />
                        </button>
                    ))}
                </div>
            )}
        </div>
    )
}
