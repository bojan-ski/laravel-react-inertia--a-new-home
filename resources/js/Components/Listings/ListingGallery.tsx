import { useCallback, useState } from 'react'
import { Maximize2 } from 'lucide-react'
import ListingLightbox from '@/Components/Listings/ListingLightbox'
import type { ListingGalleryProps } from '@/types/listing'

export default function ListingGallery({ images, title, translations }: ListingGalleryProps) {
    const [activeIndex, setActiveIndex] = useState(0)
    const [isLightboxOpen, setIsLightboxOpen] = useState(false)
    const closeLightbox = useCallback(() => setIsLightboxOpen(false), [])

    if (images.length === 0) {
        return null
    }

    const active = images[activeIndex] ?? images[0]

    return (
        <div className="flex flex-col gap-4">
            <button
                type="button"
                onClick={() => setIsLightboxOpen(true)}
                aria-label={translations.open
                    .replace(':current', String(activeIndex + 1))
                    .replace(':total', String(images.length))}
                className="group relative aspect-video w-full cursor-zoom-in overflow-hidden rounded-2xl border border-border bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
            >
                <img src={active.url} alt={active.altText || title} className="size-full object-cover" />
                <span className="absolute bottom-3 right-3 flex items-center gap-1.5 rounded-full bg-black/60 px-3 py-1.5 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100 pointer-coarse:opacity-100">
                    <Maximize2 className="size-3.5" />
                    {translations.expand}
                </span>
            </button>

            {images.length > 1 && (
                <div className="flex gap-3 overflow-x-auto pb-1">
                    {images.map((image, index) => (
                        <button
                            key={image.id}
                            type="button"
                            onClick={() => setActiveIndex(index)}
                            aria-current={index === activeIndex}
                            className={`aspect-4/3 h-20 shrink-0 overflow-hidden rounded-lg border transition-colors ${
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

            {isLightboxOpen && (
                <ListingLightbox
                    images={images}
                    title={title}
                    initialIndex={activeIndex}
                    onClose={closeLightbox}
                    translations={translations}
                />
            )}
        </div>
    )
}
