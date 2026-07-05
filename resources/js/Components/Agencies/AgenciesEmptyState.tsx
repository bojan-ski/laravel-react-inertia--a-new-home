import { router } from '@inertiajs/react'
import { Building2 } from 'lucide-react'
import type { AgenciesEmptyStateProps } from '@/types/agency'

export default function AgenciesEmptyState({ translations, hasActiveSearch }: AgenciesEmptyStateProps) {
    function reset() {
        router.get('/agencies', {}, { preserveState: true, replace: true })
    }

    return (
        <div className="flex flex-col items-center justify-center gap-3 py-24 text-center">
            <Building2 className="size-10 text-text-muted" />
            <p className="text-lg font-medium text-text-soft">{translations.headline}</p>

            {hasActiveSearch ? (
                <p className="text-sm text-text-muted">
                    {translations.hint}{' '}
                    <button
                        type="button"
                        onClick={reset}
                        className="font-medium text-brand underline underline-offset-2 transition-colors hover:text-brand-dark"
                    >
                        {translations.reset}
                    </button>
                </p>
            ) : (
                <p className="text-sm text-text-muted">{translations.none}</p>
            )}
        </div>
    )
}
