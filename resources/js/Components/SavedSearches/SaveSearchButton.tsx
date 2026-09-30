import { useCallback, useState } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { Bookmark } from 'lucide-react'
import SaveSearchDialog from '@/Components/SavedSearches/SaveSearchDialog'
import type { SaveSearchButtonProps } from '@/types/saved-search'
import type { SharedProps } from '@/types/shared'

const buttonClass =
    'inline-flex min-h-11 shrink-0 items-center gap-1.5 text-[15px] text-ink-muted transition-colors duration-150 hover:text-forest'

export default function SaveSearchButton({ filters, suggestedName, summary, translations }: SaveSearchButtonProps) {
    const { auth } = usePage<SharedProps>().props
    const [open, setOpen] = useState(false)
    const close = useCallback(() => setOpen(false), [])

    const content = (
        <>
            <Bookmark className="size-4" strokeWidth={1.25} aria-hidden="true" />
            {translations.action}
        </>
    )

    if (auth.user === null) {
        return (
            <Link href="/login" className={buttonClass}>
                {content}
            </Link>
        )
    }

    return (
        <>
            <button type="button" onClick={() => setOpen(true)} className={buttonClass}>
                {content}
            </button>

            {open && (
                <SaveSearchDialog
                    open={open}
                    onClose={close}
                    filters={filters}
                    suggestedName={suggestedName}
                    summary={summary}
                    translations={translations}
                />
            )}
        </>
    )
}
