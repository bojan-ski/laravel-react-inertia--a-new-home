import { useCallback, useState } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { Bookmark } from 'lucide-react'
import SaveSearchDialog from '@/Components/SavedSearches/SaveSearchDialog'
import type { SaveSearchButtonProps } from '@/types/saved-search'
import type { SharedProps } from '@/types/shared'

const buttonClass =
    'flex items-center gap-1.5 rounded-lg border border-border-strong px-4 py-2.5 text-sm font-medium text-text-soft transition-colors hover:border-text hover:text-text'

export default function SaveSearchButton({ filters, suggestedName, summary, translations }: SaveSearchButtonProps) {
    const { auth } = usePage<SharedProps>().props
    const [open, setOpen] = useState(false)
    const close = useCallback(() => setOpen(false), [])

    if (auth.user === null) {
        return (
            <Link href="/login" className={buttonClass}>
                <Bookmark className="size-4" />
                {translations.action}
            </Link>
        )
    }

    return (
        <>
            <button type="button" onClick={() => setOpen(true)} className={buttonClass}>
                <Bookmark className="size-4" />
                {translations.action}
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
