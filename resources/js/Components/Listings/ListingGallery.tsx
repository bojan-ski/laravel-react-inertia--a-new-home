import { useCallback, useState } from 'react'
import ListingLightbox from '@/Components/Listings/ListingLightbox'
import { useSwipe } from '@/Components/Listings/useSwipe'
import type { ListingGalleryProps, ListingImageData } from '@/types/listing'

export default function ListingGallery({ images, title, translations }: ListingGalleryProps) {
    const [slideIndex, setSlideIndex] = useState(0)
    const [lightboxIndex, setLightboxIndex] = useState<number | null>(null)
    const closeLightbox = useCallback(() => setLightboxIndex(null), [])
    const total = images.length
    const hasMany = total > 1

    function showPrevious() {
        setSlideIndex((current) => (current - 1 + total) % total)
    }

    function showNext() {
        setSlideIndex((current) => (current + 1) % total)
    }

    const swipe = useSwipe(showPrevious, showNext, hasMany)

    if (total === 0) {
        return null
    }

    const label = (index: number) =>
        translations.open.replace(':current', String(index + 1)).replace(':total', String(total))
    const slide = images[slideIndex] ?? images[0]
    const tiles = images.slice(0, 3)
    const photo = (image: ListingImageData, index: number) => (
        <button
            type="button"
            onClick={() => setLightboxIndex(index)}
            aria-label={label(index)}
            className="block size-full cursor-zoom-in overflow-hidden bg-sand"
        >
            <img src={image.url} alt={image.altText || title} className="size-full object-cover" />
        </button>
    )

    return (
        <>
            <div className="relative h-75 touch-pan-y overflow-hidden bg-sand md:h-110 lg:hidden" {...swipe}>
                <button
                    type="button"
                    onClick={() => setLightboxIndex(slideIndex)}
                    aria-label={label(slideIndex)}
                    className="block size-full cursor-zoom-in"
                >
                    <img
                        src={slide.url}
                        alt={slide.altText || title}
                        draggable={false}
                        className="size-full object-cover select-none"
                    />
                </button>

                {hasMany && (
                    <>
                        <button
                            type="button"
                            onClick={showPrevious}
                            aria-label={translations.previous}
                            className="absolute top-0 bottom-10 left-0 w-[30%] cursor-w-resize"
                        />
                        <button
                            type="button"
                            onClick={showNext}
                            aria-label={translations.next}
                            className="absolute top-0 right-0 bottom-10 w-[30%] cursor-e-resize"
                        />
                    </>
                )}

                <span
                    className="pointer-events-none absolute right-3 bottom-3 bg-paper px-2.5 py-1.5 text-xs text-ink tabular-nums"
                    aria-live="polite"
                >
                    {translations.counter.replace(':current', String(slideIndex + 1)).replace(':total', String(total))}
                </span>
            </div>

            {total === 1 ? (
                <div className="hidden h-140 lg:block xl:h-174">{photo(images[0], 0)}</div>
            ) : (
                <div className="hidden grid-cols-[minmax(0,8fr)_minmax(0,4fr)] grid-rows-[280px_280px] gap-4 lg:grid xl:grid-rows-[340px_340px]">
                    {tiles.map((image, index) => (
                        <div key={image.id} className={`relative ${index === 0 || total === 2 ? 'row-span-2' : ''}`}>
                            {photo(image, index)}

                            {index === 2 && total > 3 && (
                                <button
                                    type="button"
                                    onClick={() => setLightboxIndex(0)}
                                    className="absolute right-4 bottom-4 flex gap-6 bg-paper px-4 py-3 text-sm text-ink transition-colors duration-150 hover:bg-sand"
                                >
                                    {translations.all_photos.replace(':count', String(total))}
                                    <span aria-hidden="true">↗</span>
                                </button>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {lightboxIndex !== null && (
                <ListingLightbox
                    images={images}
                    title={title}
                    initialIndex={lightboxIndex}
                    onClose={closeLightbox}
                    translations={translations}
                />
            )}
        </>
    )
}
