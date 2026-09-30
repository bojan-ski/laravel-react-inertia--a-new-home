import { useEffect, useRef } from 'react'
import { ChevronDown } from 'lucide-react'
import type { PopoverProps } from '@/types/ui'

const widthClasses = {
    sm: 'w-80',
    md: 'w-140',
}

export default function Popover({
    id,
    label,
    open,
    onOpenChange,
    active = false,
    align = 'start',
    width = 'sm',
    chevron = true,
    children,
}: PopoverProps) {
    const wrapperRef = useRef<HTMLDivElement>(null)
    const triggerRef = useRef<HTMLButtonElement>(null)

    useEffect(() => {
        if (!open) {
            return
        }

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                onOpenChange(false)
                triggerRef.current?.focus()
            }
        }

        function handlePointerDown(event: PointerEvent) {
            if (!wrapperRef.current?.contains(event.target as Node)) {
                onOpenChange(false)
            }
        }

        document.addEventListener('keydown', handleKeyDown)
        document.addEventListener('pointerdown', handlePointerDown)

        return () => {
            document.removeEventListener('keydown', handleKeyDown)
            document.removeEventListener('pointerdown', handlePointerDown)
        }
    }, [open, onOpenChange])

    return (
        <div ref={wrapperRef} className="relative">
            <button
                ref={triggerRef}
                type="button"
                aria-expanded={open}
                aria-controls={id}
                onClick={() => onOpenChange(!open)}
                className={`inline-flex min-h-11 items-center gap-1.5 transition-colors duration-150 hover:text-forest ${
                    active ? 'text-forest' : 'text-ink'
                }`}
            >
                {label}
                {chevron && (
                    <ChevronDown
                        className={`size-3.5 transition-transform duration-150 ${open ? 'rotate-180' : ''}`}
                        strokeWidth={1.25}
                        aria-hidden="true"
                    />
                )}
            </button>

            {open && (
                <div
                    id={id}
                    className={`absolute top-full z-40 mt-2 flex flex-col gap-6 border border-rule bg-paper p-6 text-ink shadow-md ${
                        widthClasses[width]
                    } ${align === 'end' ? 'right-0' : 'left-0'}`}
                >
                    {children}
                </div>
            )}
        </div>
    )
}
