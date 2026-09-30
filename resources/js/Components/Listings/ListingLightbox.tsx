import { useEffect, useRef, useState } from 'react'
import { X } from 'lucide-react'
import { useSwipe } from '@/Components/Listings/useSwipe'
import { useFocusTrap } from '@/Components/UI/useFocusTrap'
import type { ListingGalleryTranslations, ListingImageData } from '@/types/listing'

const arrowClass =
    'flex h-full w-full items-center justify-center text-[28px] font-extralight text-paper/80 transition-colors duration-150 hover:text-paper focus-visible:outline-paper'

export interface ListingLightboxProps {
    images: ListingImageData[]
    title: string
    initialIndex: number
    onClose: () => void
    translations: ListingGalleryTranslations
}

function photoLabel(template: string, index: number, total: number): string {
    return template.replace(':current', String(index + 1)).replace(':total', String(total))
}

export default function ListingLightbox({ images, title, initialIndex, onClose, translations }: ListingLightboxProps) {
    const [index, setIndex] = useState(initialIndex)
    const [returnFocusTo] = useState(() =>
        document.activeElement instanceof HTMLElement ? document.activeElement : null,
    )
    const closeRef = useRef<HTMLButtonElement>(null)
    const activeThumbRef = useRef<HTMLButtonElement>(null)
    const panelRef = useFocusTrap(true, onClose, closeRef)
    const hasMany = images.length > 1
    const image = images[index] ?? images[0]

    function showPrevious() {
        setIndex((current) => (current - 1 + images.length) % images.length)
    }

    function showNext() {
        setIndex((current) => (current + 1) % images.length)
    }

    const swipe = useSwipe(showPrevious, showNext, hasMany)

    useEffect(() => {
        const previousOverflow = document.body.style.overflow

        document.body.style.overflow = 'hidden'

        return () => {
            document.body.style.overflow = previousOverflow
            returnFocusTo?.focus()
        }
    }, [returnFocusTo])

    useEffect(() => {
        if (!hasMany) {
            return
        }

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault()
                setIndex((current) => (current - 1 + images.length) % images.length)
            } else if (event.key === 'ArrowRight') {
                event.preventDefault()
                setIndex((current) => (current + 1) % images.length)
            }
        }

        document.addEventListener('keydown', handleKeyDown)

        return () => document.removeEventListener('keydown', handleKeyDown)
    }, [hasMany, images.length])

    useEffect(() => {
        activeThumbRef.current?.scrollIntoView({ block: 'nearest', inline: 'nearest' })
    }, [index])

    return (
        <div
            ref={panelRef}
            role="dialog"
            aria-modal="true"
            aria-label={translations.dialog_label}
            className="fixed inset-0 z-60 flex flex-col bg-[rgba(12,18,14,0.96)] text-paper"
        >
            <div className="flex items-center justify-between px-5 py-4 text-sm lg:px-10 lg:py-7">
                <span className="tabular-nums" aria-live="polite">
                    {photoLabel(translations.counter, index, images.length)}
                </span>
                <button
                    ref={closeRef}
                    type="button"
                    onClick={onClose}
                    aria-label={translations.close}
                    className="flex min-h-11 items-center gap-2.5 focus-visible:outline-paper"
                >
                    {translations.close_text}
                    <X className="size-5" strokeWidth={1.25} aria-hidden="true" />
                </button>
            </div>

            <div className="grid min-h-0 flex-1 grid-cols-[48px_minmax(0,1fr)_48px] grid-rows-[minmax(0,1fr)] pb-6 lg:grid-cols-[96px_minmax(0,1fr)_96px]">
                {hasMany ? (
                    <button type="button" onClick={showPrevious} aria-label={translations.previous} className={arrowClass}>
                        <span aria-hidden="true">←</span>
                    </button>
                ) : (
                    <span />
                )}

                <div className="relative touch-pan-y touch-pinch-zoom" {...swipe}>
                    <img
                        src={image.url}
                        alt={image.altText || title}
                        draggable={false}
                        className="absolute inset-0 size-full object-contain select-none"
                    />
                </div>

                {hasMany ? (
                    <button type="button" onClick={showNext} aria-label={translations.next} className={arrowClass}>
                        <span aria-hidden="true">→</span>
                    </button>
                ) : (
                    <span />
                )}
            </div>

            {hasMany && (
                <div className="flex justify-center-safe gap-2.5 overflow-x-auto px-5 pt-1 pb-8 scrollbar-none">
                    {images.map((thumb, thumbIndex) => {
                        const active = thumbIndex === index

                        return (
                            <button
                                key={thumb.id}
                                ref={active ? activeThumbRef : undefined}
                                type="button"
                                onClick={() => setIndex(thumbIndex)}
                                aria-label={photoLabel(translations.thumbnail, thumbIndex, images.length)}
                                aria-current={active ? 'true' : undefined}
                                className={`h-12 w-18 shrink-0 transition-opacity duration-150 focus-visible:outline-paper ${
                                    active
                                        ? 'opacity-100 outline outline-offset-2 outline-paper'
                                        : 'opacity-50 hover:opacity-80'
                                }`}
                            >
                                <img
                                    src={thumb.thumbUrl}
                                    alt=""
                                    loading="lazy"
                                    draggable={false}
                                    className="size-full object-cover"
                                />
                            </button>
                        )
                    })}
                </div>
            )}
        </div>
    )
}
