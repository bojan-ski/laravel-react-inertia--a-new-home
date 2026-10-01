import { useEffect, useId, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { X } from 'lucide-react'
import { useFocusTrap } from '@/Components/UI/useFocusTrap'
import type { DialogActionsProps, DialogProps, ModalPanelProps, SheetPanelProps } from '@/types/ui'

export function DialogActions({ children }: DialogActionsProps) {
    return <div className="flex flex-wrap justify-end gap-2.5">{children}</div>
}

function SheetPanel({ onClose, title, initialFocusRef, closeLabel, footer, children }: SheetPanelProps) {
    const titleId = useId()
    const closeRef = useRef<HTMLButtonElement>(null)
    const [returnFocusTo] = useState(() =>
        document.activeElement instanceof HTMLElement ? document.activeElement : null,
    )
    const panelRef = useFocusTrap(true, onClose, initialFocusRef ?? closeRef)

    useEffect(() => {
        const previousOverflow = document.body.style.overflow

        document.body.style.overflow = 'hidden'

        return () => {
            document.body.style.overflow = previousOverflow
            returnFocusTo?.focus()
        }
    }, [returnFocusTo])

    return (
        <div
            ref={panelRef}
            role="dialog"
            aria-modal="true"
            aria-labelledby={titleId}
            className="fixed inset-0 z-60 flex flex-col bg-paper text-ink"
        >
            <div className="flex items-center justify-between gap-4 border-b border-rule py-2 pr-2.5 pl-5 md:pr-7.5 md:pl-10">
                <h2 id={titleId} className="text-[28px]/[1.1] font-airy tracking-[-0.03em]">
                    {title}
                </h2>
                <button
                    ref={closeRef}
                    type="button"
                    onClick={onClose}
                    aria-label={closeLabel}
                    className="flex size-11 items-center justify-center transition-colors duration-150 hover:text-forest"
                >
                    <X className="size-5.5" strokeWidth={1.25} aria-hidden="true" />
                </button>
            </div>

            <div className="flex-1 overflow-y-auto px-5 py-6 md:px-10">{children}</div>

            {footer && <div className="border-t border-rule px-5 py-4 md:px-10">{footer}</div>}
        </div>
    )
}

function ModalPanel({ onClose, title, description, size, initialFocusRef, children }: ModalPanelProps) {
    const titleId = useId()
    const descriptionId = useId()
    const [returnFocusTo] = useState(() =>
        document.activeElement instanceof HTMLElement ? document.activeElement : null,
    )
    const panelRef = useFocusTrap(true, onClose, initialFocusRef)

    useEffect(() => () => returnFocusTo?.focus(), [returnFocusTo])

    return (
        <div className="fixed inset-0 z-60 flex items-center justify-center bg-[#141a16]/45 p-4" onClick={onClose}>
            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                aria-describedby={description ? descriptionId : undefined}
                className={`flex max-h-full w-full flex-col gap-5.5 overflow-y-auto bg-paper p-6 text-ink shadow-lg sm:p-10 ${
                    size === 'confirm' ? 'max-w-110' : 'max-w-130'
                }`}
                onClick={(event) => event.stopPropagation()}
            >
                <div className="flex flex-col gap-2">
                    <h2 id={titleId} className="text-[28px]/[1.1] font-airy tracking-[-0.03em] sm:text-[34px]/[1.1]">
                        {title}
                    </h2>
                    {description && (
                        <div id={descriptionId} className="text-sm/[1.55] text-ink-muted">
                            {description}
                        </div>
                    )}
                </div>

                {children}
            </div>
        </div>
    )
}

export default function Dialog({
    open,
    onClose,
    title,
    description,
    size = 'form',
    initialFocusRef,
    closeLabel,
    footer,
    children,
}: DialogProps) {
    if (!open) {
        return null
    }

    if (size === 'sheet') {
        return createPortal(
            <SheetPanel
                onClose={onClose}
                title={title}
                initialFocusRef={initialFocusRef}
                closeLabel={closeLabel}
                footer={footer}
            >
                {children}
            </SheetPanel>,
            document.body,
        )
    }

    return createPortal(
        <ModalPanel
            onClose={onClose}
            title={title}
            description={description}
            size={size}
            initialFocusRef={initialFocusRef}
        >
            {children}
        </ModalPanel>,
        document.body,
    )
}
