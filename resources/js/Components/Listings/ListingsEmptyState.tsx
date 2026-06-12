import { router } from '@inertiajs/react'
import type { ListingsEmptyStateProps } from '@/types/listing'

export default function ListingsEmptyState({ translations, hasActiveFilters }: ListingsEmptyStateProps) {
    function reset() {
        router.get('/listings', {}, { preserveScroll: true, replace: true })
    }

    return (
        <div className="flex flex-col items-center justify-center gap-3 py-24 text-center">
            <p className="text-lg font-medium text-text">{translations.headline}</p>

            {hasActiveFilters && (
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
            )}
        </div>
    )
}
