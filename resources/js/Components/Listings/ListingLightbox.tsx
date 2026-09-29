import { useEffect, useRef, useState, type PointerEvent } from 'react'
import { ChevronLeft, ChevronRight, X } from 'lucide-react'
import { useFocusTrap } from '@/Components/UI/useFocusTrap'
import type { ListingGalleryTranslations, ListingImageData } from '@/types/listing'

const SWIPE_THRESHOLD = 50

const iconButtonClass =
    'flex size-11 items-center justify-center rounded-full text-white transition-colors hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-white'

export interface ListingLightboxProps {
    images: ListingImageData[]
    title: string
    initialIndex: number
    onClose: () => void
    translations: ListingGalleryTranslations
}

export default function ListingLightbox({ images, title, initialIndex, onClose, translations }: ListingLightboxProps) {
    const [index, setIndex] = useState(initialIndex)
    const [returnFocusTo] = useState(() =>
        document.activeElement instanceof HTMLElement ? document.activeElement : null,
    )
    const closeRef = useRef<HTMLButtonElement>(null)
    const pointerStartX = useRef<number | null>(null)
    const panelRef = useFocusTrap(true, onClose, closeRef)
    const hasMany = images.length > 1
    const image = images[index] ?? images[0]

    function showPrevious() {
        setIndex((current) => (current - 1 + images.length) % images.length)
    }

    function showNext() {
        setIndex((current) => (current + 1) % images.length)
    }

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

    function handlePointerDown(event: PointerEvent<HTMLDivElement>) {
        pointerStartX.current = event.clientX
    }

    function handlePointerUp(event: PointerEvent<HTMLDivElement>) {
        if (pointerStartX.current === null || !hasMany) {
            return
        }

        const delta = event.clientX - pointerStartX.current
        pointerStartX.current = null

        if (delta > SWIPE_THRESHOLD) {
            showPrevious()
        } else if (delta < -SWIPE_THRESHOLD) {
            showNext()
        }
    }

    return (
        <div
            ref={panelRef}
            role="dialog"
            aria-modal="true"
            aria-label={translations.dialog_label}
            className="fixed inset-0 z-60 flex flex-col bg-black/90"
        >
            <div className="flex justify-end p-2 sm:p-4">
                <button
                    ref={closeRef}
                    type="button"
                    onClick={onClose}
                    aria-label={translations.close}
                    className={iconButtonClass}
                >
                    <X className="size-6" />
                </button>
            </div>

            <div
                className="relative flex min-h-0 flex-1 touch-pan-y touch-pinch-zoom items-center justify-center px-2 sm:px-20"
                onPointerDown={handlePointerDown}
                onPointerUp={handlePointerUp}
                onPointerCancel={() => (pointerStartX.current = null)}
            >
                <img
                    src={image.url}
                    alt={image.altText || title}
                    draggable={false}
                    className="max-h-full max-w-full select-none object-contain"
                />

                {hasMany && (
                    <>
                        <button
                            type="button"
                            onClick={showPrevious}
                            aria-label={translations.previous}
                            className={`${iconButtonClass} absolute left-1 top-1/2 -translate-y-1/2 bg-black/40 sm:left-4`}
                        >
                            <ChevronLeft className="size-7" />
                        </button>
                        <button
                            type="button"
                            onClick={showNext}
                            aria-label={translations.next}
                            className={`${iconButtonClass} absolute right-1 top-1/2 -translate-y-1/2 bg-black/40 sm:right-4`}
                        >
                            <ChevronRight className="size-7" />
                        </button>
                    </>
                )}
            </div>

            <p className="py-4 text-center text-sm text-text-inverse/80" aria-live="polite">
                {translations.counter.replace(':current', String(index + 1)).replace(':total', String(images.length))}
            </p>
        </div>
    )
}
