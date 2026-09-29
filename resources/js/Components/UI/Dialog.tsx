import { useId } from 'react'
import { createPortal } from 'react-dom'
import { useFocusTrap } from '@/Components/UI/useFocusTrap'
import type { DialogActionsProps, DialogProps } from '@/types/ui'

export function DialogActions({ children }: DialogActionsProps) {
    return <div className="flex flex-wrap justify-end gap-2.5">{children}</div>
}

export default function Dialog({
    open,
    onClose,
    title,
    description,
    size = 'form',
    initialFocusRef,
    children,
}: DialogProps) {
    const titleId = useId()
    const descriptionId = useId()
    const panelRef = useFocusTrap(open, onClose, initialFocusRef)

    if (!open) {
        return null
    }

    return createPortal(
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
        </div>,
        document.body,
    )
}
